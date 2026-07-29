<?php

declare(strict_types=1);

namespace App\Tests\Unit\ClientManagement\Domain\Model;

use App\ClientManagement\Domain\Model\Contact;
use PHPUnit\Framework\TestCase;

class ContactTest extends TestCase
{
    public function testCreate(): void
    {
        $contact = Contact::create('Jane', 'Doe', 'jane@example.com', '555-0100');

        $this->assertNotEmpty($contact->id());
        $this->assertSame('Jane', $contact->firstName());
        $this->assertSame('Doe', $contact->lastName());
        $this->assertSame('jane@example.com', $contact->email());
        $this->assertSame('555-0100', $contact->phone());
    }

    public function testCreateWithoutPhone(): void
    {
        $contact = Contact::create('Bob', 'Smith', 'bob@example.com');

        $this->assertSame('Bob', $contact->firstName());
        $this->assertNull($contact->phone());
    }

    public function testCreateGeneratesUniqueIds(): void
    {
        $a = Contact::create('A', 'B', 'a@b.com');
        $b = Contact::create('C', 'D', 'c@d.com');

        $this->assertNotSame($a->id(), $b->id());
    }
}
