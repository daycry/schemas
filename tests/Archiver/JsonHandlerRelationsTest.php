<?php

declare(strict_types=1);

/**
 * This file is part of Daycry Schemas.
 *
 * (c) Daycry <daycry9@proton.me>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Archiver;

use Daycry\Schemas\Archiver\Handlers\JsonHandler;
use Daycry\Schemas\Structures\Field;
use Daycry\Schemas\Structures\ForeignKey;
use Daycry\Schemas\Structures\Index;
use Daycry\Schemas\Structures\Mergeable;
use Daycry\Schemas\Structures\Relation;
use Daycry\Schemas\Structures\Schema;
use Daycry\Schemas\Structures\Table;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class JsonHandlerRelationsTest extends TestCase
{
    private string $file;
    private JsonHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->file    = sys_get_temp_dir() . '/schema_rel_' . uniqid() . '.json';
        $this->handler = new JsonHandler(null, $this->file, true);
    }

    protected function tearDown(): void
    {
        if (is_file($this->file)) {
            @unlink($this->file);
        }
        parent::tearDown();
    }

    public function testArchiveAndImportWithRelationsAndForeignKeys(): void
    {
        $schema         = new Schema();
        $schema->tables = new Mergeable();

        // users table
        $users             = new Table('users');
        $users->fields     = new Mergeable();
        $id                = new Field('id');
        $id->type          = 'INT';
        $id->primary_key   = true;
        $users->fields->id = $id;

        // posts table with FK + relation
        $posts                  = new Table('posts');
        $posts->fields          = new Mergeable();
        $posts->indexes         = new Mergeable();
        $posts->foreignKeys     = new Mergeable();
        $posts->relations       = new Mergeable();
        $pid                    = new Field('id');
        $pid->type              = 'INT';
        $pid->primary_key       = true;
        $posts->fields->id      = $pid;
        $uid                    = new Field('user_id');
        $uid->type              = 'INT';
        $posts->fields->user_id = $uid;

        $idx                      = new Index('idx_user');
        $idx->fields              = ['user_id'];
        $idx->unique              = false;
        $posts->indexes->idx_user = $idx;

        $fk                                = new ForeignKey();
        $fk->constraint_name               = 'fk_posts_user';
        $fk->column_name                   = 'user_id';
        $fk->foreign_table_name            = 'users';
        $fk->foreign_column_name           = 'id';
        $fk->on_delete                     = 'CASCADE';
        $fk->on_update                     = 'RESTRICT';
        $posts->foreignKeys->fk_posts_user = $fk;

        $rel                    = new Relation();
        $rel->type              = 'belongsTo';
        $rel->table             = 'users';
        $rel->field             = 'user_id';
        $posts->relations->user = $rel;

        $schema->tables->users = $users;
        $schema->tables->posts = $posts;

        $this->assertTrue($this->handler->archive($schema));
        $json = file_get_contents($this->file);
        $this->assertNotFalse($json);
        $this->assertStringContainsString('fk_posts_user', $json);
        $this->assertStringContainsString('belongsTo', $json);

        $imported = $this->handler->load();
        $this->assertNotNull($imported);
        $this->assertTrue(property_exists($imported->tables, 'posts'));
        $postTable = $imported->tables->posts;
        $this->assertTrue(property_exists($postTable->relations, 'user'));
        $this->assertSame('users', $postTable->relations->user->table);
        $this->assertTrue(property_exists($postTable->foreignKeys, 'fk_posts_user'));
    }
}
