<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

namespace ILIAS\Filesystem\Stream;

use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\BackupStaticProperties;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Small;
use ILIAS\Filesystem\Util\PHPStreamFunctions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @author                 Nicolas Schäfli <ns@studer-raimann.ch>
 * @author                 Fabian Schmid <fabian@sr.solutions>
 */
#[BackupGlobals(false)]
#[BackupStaticProperties(false)]
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
class StreamTest extends TestCase
{
    /**
     * @var Mockery\MockInterface
     */
    public static $functions;

    private function createResource(string $content, string $mode)
    {
        //call the root fopen function \ required!
        return \fopen("data://text/plain,$content", $mode);
    }


    protected function setUp(): void
    {
        parent::setUp();

        self::$functions = Mockery::mock();
    }


    public function testDetachWhichShouldSucceed(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);
        $detachedResource = $subject->detach();

        //check that the resource is valid.
        $this->assertTrue(is_resource($detachedResource));
        $this->assertSame($resource, $detachedResource);

        //Can't test the subject because psr-7 defines that the stream is in an unusable after the detach operation.
    }


    public function testDetachDoubleInvocationWhichShouldFail(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);

        //check that the detached resource is valid.
        $detachedResource = $subject->detach();
        $this->assertTrue(is_resource($detachedResource));

        //must be null because the stream was already detached.
        $detachedResource = $subject->detach();
        $this->assertNull($detachedResource);
    }


    public function testGetSizeWithStatsWhichShouldSucceed(): void
    {
        $content = 'awesome content stream';
        $correctSize = strlen($content);
        $mode = 'r';
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);

        $size = $subject->getSize();
        $this->assertSame($correctSize, $size);
    }


    public function testGetSizeWithOptionsWhichShouldSucceed(): void
    {
        $content = 'awesome content stream';
        $correctSize = 900;
        $mode = 'r';
        $resource = $this->createResource($content, $mode);
        $options = new StreamOptions([], $correctSize);

        $subject = new Stream($resource, $options);

        $size = $subject->getSize();
        $this->assertSame($correctSize, $size);
    }

    public function testGetSizeWithDetachedStreamWhichShouldFail(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);
        $subject->detach();

        $size = $subject->getSize();
        $this->assertNull($size);
    }

    public function testCloseWhichShouldSucceed(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);

        $subject->close();
        $this->assertFalse(is_resource($resource));
    }

    public function testCloseWithDetachedStreamWhichShouldDoNothing(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);

        $actualResource = $subject->detach();
        $subject->close();

        $this->assertTrue(is_resource($actualResource));
    }

    public function testTellWhichShouldSucceed(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $offset = 5;
        $resource = $this->createResource($content, $mode);
        fseek($resource, $offset);

        $subject = new Stream($resource);

        $actualPosition = $subject->tell();
        $this->assertSame($offset, $actualPosition);
    }

    public function testTellWithDetachedStreamWhichShouldFail(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);
        $subject->detach();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached');

        $subject->tell();
    }

    public function testTellWithFtellFailureWhichShouldFail(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $resource = $this->createResource($content, $mode);

        //load mock class
        $functionMock = Mockery::mock('alias:' . PHPStreamFunctions::class);
        $functionMock->shouldReceive('ftell')
            ->once()
            ->with($resource)
            ->andReturn(false);

        $functionMock->shouldReceive('fclose')
            ->once()
            ->with($resource);

        $subject = new Stream($resource);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to determine stream position');

        $subject->tell();
    }

    #[Test]
    #[Small]
    public function testEofWhichShouldSucceed(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $offset = strlen($content); // end of stream
        $resource = $this->createResource($content, $mode);
        fseek($resource, $offset);  // seek to end of stream
        fgets($resource, 2); // we need to hit the end of the stream or eof returns false. (https://bugs.php.net/bug.php?id=35136)

        $subject = new Stream($resource);

        $endOfFileReached = $subject->eof();
        $this->assertTrue($endOfFileReached);
    }

    #[Test]
    #[Small]
    public function testEofWithDetachedStreamWhichShouldFail(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);
        $subject->detach();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached');

        $subject->eof();
    }


    #[Test]
    #[Small]
    public function testSeekWhichShouldSucceed(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $offset = 5;
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);

        $subject->seek($offset);
        $this->assertSame($offset, ftell($resource));
    }

    #[Test]
    #[Small]
    public function testSeekWithDetachedStreamWhichShouldFail(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $offset = 5;
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);
        $subject->detach();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached');

        $subject->seek($offset);
    }

    #[Test]
    #[Small]
    public function testSeekWithNotSeekableStreamWhichShouldFail(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $offset = 5;
        $resource = $this->createResource($content, $mode);

        $subjectMock = Mockery::mock(Stream::class . '[isSeekable]', [$resource]);

        $subjectMock
            ->shouldReceive('isSeekable')
            ->once()
            ->andReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream is not seekable');

        $subjectMock->seek($offset);
    }

    #[Test]
    #[Small]
    public function testSeekWithFseekFailureWhichShouldFail(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $offset = 5;
        $whence = SEEK_SET;
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);

        //load mock class
        $functionMock = Mockery::mock('alias:' . PHPStreamFunctions::class);
        $functionMock->shouldReceive('fseek')
            ->once()
            ->withArgs([$resource, $offset, $whence])
            ->andReturn(-1);

        $functionMock->shouldReceive('fclose')
            ->once()
            ->with($resource);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unable to seek to stream position \"$offset\" with whence \"$whence\"");

        $subject->seek($offset);
    }

    #[Test]
    #[Small]
    public function testReadWhichShouldSucceed(): void
    {
        $content = 'awesome content stream';
        $expectedResult = "awesome";
        $mode = 'r';
        $length = 7;
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);

        $text = $subject->read($length);
        $this->assertSame($expectedResult, $text);
    }

    #[Test]
    #[Small]
    public function testReadWithZeroLengthWhichShouldSucceed(): void
    {
        $content = 'awesome content stream';
        $expectedResult = "";
        $mode = 'r';
        $length = 0;
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);

        $text = $subject->read($length);
        $this->assertSame($expectedResult, $text);
    }

    #[Test]
    #[Small]
    public function testReadWithDetachedStreamWhichShouldFail(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $length = 7;
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);
        $subject->detach();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached');

        $subject->read($length);
    }

    #[Test]
    #[Small]
    public function testReadWithNegativeLengthWhichShouldFail(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $length = -2;
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Length parameter must not be negative');

        $subject->read($length);
    }

    #[Test]
    #[Small]
    public function testReadWithUnreadableStreamWhichShouldFail(): void
    {
        $content = 'awesome content stream';
        $mode = 'w';
        $length = 3;
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Can not read from non-readable stream');

        $subject->read($length);
    }

    #[Test]
    #[Small]
    public function testReadWithFailingFreadCallWhichShouldFail(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $length = 3;
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);

        //load mock class
        $functionMock = Mockery::mock('alias:' . PHPStreamFunctions::class);

        $functionMock->shouldReceive('fread')
            ->once()
            ->withArgs([$resource, $length])
            ->andReturn(false);

        $functionMock->shouldReceive('fclose')
            ->once()
            ->with($resource);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to read from stream');

        $subject->read($length);
    }

    #[Test]
    #[Small]
    public function testGetContentsWhichShouldSucceed(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);

        $text = $subject->getContents();
        $this->assertSame($content, $text);
    }

    #[Test]
    #[Small]
    public function testGetContentsWithDetachedStreamWhichShouldFail(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);
        $subject->detach();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached');

        $subject->getContents();
    }

    #[Test]
    #[Small]
    public function testGetContentsWithFailingStreamGetContentsCallWhichShouldFail(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);

        //load mock class
        $functionMock = Mockery::mock('alias:' . PHPStreamFunctions::class);

        $functionMock->shouldReceive('stream_get_contents')
            ->once()
            ->with($resource)
            ->andReturn(false);

        $functionMock->shouldReceive('fclose')
            ->once()
            ->with($resource);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to read stream contents');

        $subject->getContents();
    }

    #[Test]
    #[Small]
    public function testToStringWhichShouldSucceed(): void
    {
        $content = 'awesome content stream';
        $mode = 'r';
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);

        $text = $subject->__toString();
        $this->assertSame($content, $text);
    }

    #[Test]
    #[Small]
    public function testToStringWithErrorWhichShouldSucceed(): void
    {
        $content = 'awesome content stream';
        $expectedResult = '';
        $mode = 'r';
        $resource = $this->createResource($content, $mode);

        $subject = Mockery::mock(Stream::class . '[rewind]', [$resource]);

        $subject->shouldDeferMissing();
        $subject->shouldReceive('rewind')
            ->once()
            ->andThrow(\RuntimeException::class);

        $text = $subject->__toString();
        $this->assertSame($expectedResult, $text);
    }

    #[Test]
    #[Small]
    public function testWriteWhichShouldSucceed(): void
    {
        $content = 'awesome content stream';
        $newContent = '!';
        $byteCount = strlen($newContent);
        $mode = 'r+';
        $resource = fopen('php://memory', $mode);
        PHPStreamFunctions::fwrite($resource, $content);

        $subject = new Stream($resource);
        $currentSize = $subject->getSize();

        $numberOfBytesWritten = $subject->write($newContent);
        $newSize = $subject->getSize();

        $this->assertSame($byteCount, $numberOfBytesWritten, 'The count of bytes passed to write must match the written bytes after the operation.');
        $this->assertGreaterThan($currentSize, $newSize, 'The new size must be grater than the old size because we wrote to the stream.');
    }

    #[Test]
    #[Small]
    public function testWriteWithDetachedStreamWhichShouldFail(): void
    {
        $content = 'awesome content stream';
        $newContent = '!';
        $mode = 'w';
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);
        $subject->detach();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached');

        $subject->write($newContent);
    }

    #[Test]
    #[Small]
    public function testWriteWithReadOnlyStreamWhichShouldFail(): void
    {
        $content = 'awesome content stream';
        $newContent = '!';
        $mode = 'r';
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Can not write to a non-writable stream');

        $subject->write($newContent);
    }

    #[Test]
    #[Small]
    public function testWriteWithFailingFwriteCallWhichShouldFail(): void
    {
        $content = 'awesome content stream';
        $newContent = '!';
        $mode = 'a+';
        $resource = $this->createResource($content, $mode);

        $subject = new Stream($resource);

        //load mock class
        $functionMock = Mockery::mock('alias:' . PHPStreamFunctions::class);

        $functionMock->shouldReceive('fwrite')
            ->once()
            ->withArgs([$resource, $newContent])
            ->andReturn(false);

        $functionMock->shouldReceive('fclose')
            ->once()
            ->with($resource);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to write to stream');

        $subject->write($newContent);
    }
}
