<?php

namespace Zenstruck\Uri\Tests;

use PHPUnit\Framework\TestCase;
use Zenstruck\Uri\Mailto;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MailtoTest extends TestCase
{
    /**
     * @test
     * @dataProvider convertToStringProvider
     */
    public function convert_to_string($input, $expected): void
    {
        $this->assertSame($expected, Mailto::new($input)->toString());
    }

    public static function convertToStringProvider(): iterable
    {
        yield [null, 'mailto:'];
        yield ['', 'mailto:'];
        yield ['?subject=foo', 'mailto:?subject=foo'];
        yield ['?subject=foo&extra=bar', 'mailto:?subject=foo'];
        yield ['kevin@example.com', 'mailto:kevin%40example.com'];
        yield ['kevin%40example.com', 'mailto:kevin%40example.com'];
        yield ['kevin@example.com,fred@example.com', 'mailto:kevin%40example.com%2Cfred%40example.com'];
        yield ['kevin@example.com%2Cfred@example.com', 'mailto:kevin%40example.com%2Cfred%40example.com'];
        yield ['mailto:kevin@example.com', 'mailto:kevin%40example.com'];
        yield ['mailto:kevin@example.com,fred@example.com', 'mailto:kevin%40example.com%2Cfred%40example.com'];
        yield ['http://example.com:80/kevin@example.com#foo', 'mailto:kevin%40example.com'];
        yield ['kevin@example.com?foo=bar', 'mailto:kevin%40example.com'];
        yield ['kevin@example.com?subject=bar', 'mailto:kevin%40example.com?subject=bar'];
        yield ['kevin@example.com?subject=bar&cc=jimmy@example.com', 'mailto:kevin%40example.com?subject=bar&cc=jimmy%40example.com'];
    }

    /**
     * @test
     */
    public function can_access_parts(): void
    {
        $mailto = Mailto::new();

        $this->assertEmpty($mailto->to());
        $this->assertEmpty($mailto->cc());
        $this->assertEmpty($mailto->bcc());
        $this->assertNull($mailto->subject());
        $this->assertNull($mailto->body());

        $mailto = Mailto::new('user1@example.com,user2@example.com?subject=foo&body=bar&cc=user3@example.com,  user4@example.com&bcc=user5@example.com');

        $this->assertSame(['user1@example.com', 'user2@example.com'], $mailto->to());
        $this->assertSame(['user3@example.com', 'user4@example.com'], $mailto->cc());
        $this->assertSame(['user5@example.com'], $mailto->bcc());
        $this->assertSame('foo', $mailto->subject());
        $this->assertSame('bar', $mailto->body());
    }

    /**
     * @test
     */
    public function can_manipulate(): void
    {
        $mailto = Mailto::new();

        $this->assertSame('mailto:', (string) $mailto);

        $mailto = $mailto->withSubject('my subject')->withBody("my body\n\nsecond line");

        $this->assertSame('my subject', $mailto->subject());
        $this->assertSame("my body\n\nsecond line", $mailto->body());
        $this->assertSame('mailto:?subject=my%20subject&body=my%20body%0A%0Asecond%20line', (string) $mailto);

        $mailto = $mailto->addTo('kevin@example.com')->addCc('user1@example.com');

        $this->assertSame(['kevin@example.com'], $mailto->to());
        $this->assertSame(['user1@example.com'], $mailto->cc());
        $this->assertSame('mailto:kevin%40example.com?subject=my%20subject&body=my%20body%0A%0Asecond%20line&cc=user1%40example.com', (string) $mailto);

        $mailto = $mailto->withoutSubject()->withoutBody()->addCc('user2@example.com');

        $this->assertSame('mailto:kevin%40example.com?cc=user1%40example.com%2Cuser2%40example.com', (string) $mailto);

        $mailto = $mailto->withoutSubject()->withoutBody()->withCc('user2@example.com');

        $this->assertSame('mailto:kevin%40example.com?cc=user2%40example.com', (string) $mailto);

        $mailto = $mailto->withoutCc()->addBcc('user2@example.com');

        $this->assertSame('mailto:kevin%40example.com?bcc=user2%40example.com', (string) $mailto);

        $mailto = $mailto->withoutTo()->withoutBcc();

        $this->assertSame('mailto:', (string) $mailto);
    }

    /**
     * @test
     */
    public function immutable(): void
    {
        $mailto = Mailto::new();

        $this->assertNotSame($mailto, $mailto->withSubject('value'));
        $this->assertNotSame($mailto, $mailto->withBody('value'));
        $this->assertNotSame($mailto, $mailto->withTo('value'));
        $this->assertNotSame($mailto, $mailto->addTo('value'));
        $this->assertNotSame($mailto, $mailto->withoutTo());
        $this->assertNotSame($mailto, $mailto->withCc('value'));
        $this->assertNotSame($mailto, $mailto->addCc('value'));
        $this->assertNotSame($mailto, $mailto->withoutCc());
        $this->assertNotSame($mailto, $mailto->withBcc('value'));
        $this->assertNotSame($mailto, $mailto->addBcc('value'));
        $this->assertNotSame($mailto, $mailto->withoutBcc());
    }

    /**
     * @test
     */
    public function can_add_emails_with_names(): void
    {
        $mailto = Mailto::new()
            ->withSubject('my subject')
            ->withBody("my body\n\nsecond line")
            ->addTo('kevin@example.com', 'Kevin')
            ->addCc('user2@example.com', 'User2')
            ->addBCc('user3@example.com', 'User3')
        ;

        $this->assertSame(['Kevin <kevin@example.com>'], $mailto->to());
        $this->assertSame(['User2 <user2@example.com>'], $mailto->cc());
        $this->assertSame(['User3 <user3@example.com>'], $mailto->bcc());
        $this->assertSame('mailto:Kevin%20%3Ckevin%40example.com%3E?subject=my%20subject&body=my%20body%0A%0Asecond%20line&cc=User2%20%3Cuser2%40example.com%3E&bcc=User3%20%3Cuser3%40example.com%3E', (string) $mailto);
    }
}
