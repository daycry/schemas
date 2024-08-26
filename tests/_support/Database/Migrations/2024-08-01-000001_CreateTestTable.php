<?php

declare(strict_types=1);

namespace Tests\Daycry\Schemas\Database\Migrations;

use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateCoreTables extends Migration
{
    public function __construct(?Forge $forge = null)
    {
        $this->DBGroup = 'tests';

        parent::__construct($forge);
    }

    public function up(): void
    {
        $this->db->disableForeignKeyChecks();

        // Users Table
        $this->forge->addField([
            'id'             => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'username'       => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'status'         => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'status_message' => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'active'         => ['type' => 'tinyint', 'constraint' => 1, 'null' => false, 'default' => 0],
            'last_active'    => ['type' => 'datetime', 'null' => true, 'default' => null],
            'created_at'     => ['type' => 'datetime', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'     => ['type' => 'datetime', 'null' => true],
            'deleted_at'     => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('username');
        $this->createTable('users');

        // Users Table
        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'datetime', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at' => ['type' => 'datetime', 'null' => true],
            'deleted_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('name');
        $this->createTable('permissions');

        // Users Permissions Table
        $this->forge->addField([
            'id'            => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'       => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'permission_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'until_at'      => ['type' => 'datetime', 'null' => true],
            'created_at'    => ['type' => 'datetime', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'    => ['type' => 'datetime', 'null' => true],
            'deleted_at'    => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('permission_id', 'permissions', 'id', '', 'CASCADE');
        $this->createTable('permissions_users');

        /*
         * Auth Identities Table
         * Used for storage of passwords, access tokens.
         */
        $this->forge->addField([
            'id'           => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'type'         => ['type' => 'varchar', 'constraint' => 255],
            'name'         => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'secret'       => ['type' => 'varchar', 'constraint' => 255],
            'secret2'      => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'extra'        => ['type' => 'text', 'null' => true],
            'expires'      => ['type' => 'datetime', 'null' => true, 'default' => null],
            'force_reset'  => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'ignore_rates' => ['type' => 'tinyint', 'constraint' => 1, 'null' => false, 'default' => 0],
            'is_private'   => ['type' => 'tinyint', 'constraint' => 1, 'null' => false, 'default' => 0],
            'ip_addresses' => ['type' => 'text', 'null' => true, 'default' => null],
            'last_used_at' => ['type' => 'datetime', 'null' => true],
            'created_at'   => ['type' => 'datetime', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'   => ['type' => 'datetime', 'null' => true],
            'deleted_at'   => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['type', 'secret']);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->createTable('identities');

        /**
         * Auth Login Attempts Table
         * Records login attempts. A login means users think it is a login.
         * To login, users do action(s) like posting a form.
         */
        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ip_address' => ['type' => 'varchar', 'constraint' => 255],
            'user_agent' => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'id_type'    => ['type' => 'varchar', 'constraint' => 255],
            'identifier' => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true], // Only for successful logins
            'date'       => ['type' => 'datetime'],
            'success'    => ['type' => 'tinyint', 'constraint' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['id_type', 'identifier']);
        $this->forge->addKey('user_id');
        // NOTE: Do NOT delete the user_id or identifier when the user is deleted for security audits
        $this->createTable('logins');

        $this->forge->addField([
            'id'              => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'selector'        => ['type' => 'varchar', 'constraint' => 255],
            'hashedValidator' => ['type' => 'varchar', 'constraint' => 255],
            'user_id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'expires'         => ['type' => 'datetime'],
            'created_at'      => ['type' => 'datetime', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'      => ['type' => 'datetime', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('selector');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->createTable('remember_tokens');

        // Groups Table
        $this->forge->addField([
            'id'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'varchar', 'constraint' => 30, 'null' => true],
            'description' => ['type' => 'text', 'null' => true],
            'created_at'  => ['type' => 'datetime', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'  => ['type' => 'datetime', 'null' => true],
            'deleted_at'  => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('groups');

        // Groups Permissions Table
        $this->forge->addField([
            'id'            => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'group_id'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'permission_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'until_at'      => ['type' => 'datetime', 'null' => true],
            'created_at'    => ['type' => 'datetime', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'    => ['type' => 'datetime', 'null' => true],
            'deleted_at'    => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('group_id', 'groups', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('permission_id', 'permissions', 'id', '', 'CASCADE');
        $this->createTable('permissions_groups');

        // Users Groups Table
        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'group_id'   => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'until_at'   => ['type' => 'datetime', 'null' => true],
            'created_at' => ['type' => 'datetime', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at' => ['type' => 'datetime', 'null' => true],
            'deleted_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'group_id']);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('group_id', 'groups', 'id', '', 'CASCADE');
        $this->forge->createTable('groups_users');

        // Logs Table
        $this->forge->addField([
            'id'            => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'       => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'default' => null],
            'uri'           => ['type' => 'varchar', 'constraint' => 255, 'null' => false],
            'method'        => ['type' => 'varchar', 'constraint' => 6, 'null' => false],
            'params'        => ['type' => 'text', 'null' => true],
            'ip_address'    => ['type' => 'varchar', 'constraint' => 45, 'null' => false],
            'duration'      => ['type' => 'float', 'null' => true, 'default' => null],
            'response_code' => ['type' => 'int', 'constraint' => 3, 'null' => true],
            'authorized'    => ['type' => 'tinyint', 'constraint' => 1, 'null' => true, 'default' => null],
            'created_at'    => ['type' => 'datetime', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'    => ['type' => 'datetime', 'null' => true, 'default' => null],
            'deleted_at'    => ['type' => 'datetime', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('logs');

        // Apis Table
        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'url'        => ['type' => 'varchar', 'constraint' => 255, 'null' => false],
            'checked_at' => ['type' => 'datetime', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'created_at' => ['type' => 'datetime', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at' => ['type' => 'datetime', 'null' => true, 'default' => null],
            'deleted_at' => ['type' => 'datetime', 'null' => true, 'default' => null],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('checked_at');
        $this->forge->addUniqueKey('url');
        $this->forge->createTable('apis');

        // Controllers Table
        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'api_id'     => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'controller' => ['type' => 'varchar', 'constraint' => 255, 'null' => false],
            'checked_at' => ['type' => 'datetime', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'created_at' => ['type' => 'datetime', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at' => ['type' => 'datetime', 'null' => true, 'default' => null],
            'deleted_at' => ['type' => 'datetime', 'null' => true, 'default' => null],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('checked_at');
        $this->forge->addKey('controller', false, true);
        $this->forge->addForeignKey('api_id', 'apis', 'id', '', 'CASCADE');
        $this->forge->createTable('controllers');

        // Endpoints Table
        $this->forge->addField([
            'id'            => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'controller_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'method'        => ['type' => 'varchar', 'constraint' => 255, 'null' => true, 'default' => null],
            'checked_at'    => ['type' => 'datetime', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'auth'          => ['type' => 'varchar', 'constraint' => 10, 'null' => true, 'default' => null],
            'access_token'  => ['type' => 'tinyint', 'constraint' => 1, 'null' => true, 'default' => null],
            'log'           => ['type' => 'tinyint', 'constraint' => 1, 'null' => true, 'default' => null],
            'limit'         => ['type' => 'tinyint', 'constraint' => 1, 'null' => true, 'default' => null],
            'time'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'default' => null],
            'scope'         => ['type' => 'varchar', 'constraint' => 255, 'null' => true, 'default' => null],
            'created_at'    => ['type' => 'datetime', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'    => ['type' => 'datetime', 'null' => true, 'default' => null],
            'deleted_at'    => ['type' => 'datetime', 'null' => true, 'default' => null],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('checked_at');
        $this->forge->addUniqueKey(['controller_id', 'method']);
        $this->forge->addForeignKey('controller_id', 'controllers', 'id', '', 'CASCADE');
        $this->forge->createTable('endpoints');

        // Attemps Table
        $this->forge->addField([
            'id'              => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'default' => null],
            'ip_address'      => ['type' => 'varchar', 'constraint' => 45, 'null' => false],
            'attempts'        => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => false, 'default' => 0],
            'hour_started_at' => ['type' => 'datetime', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'created_at'      => ['type' => 'datetime', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'      => ['type' => 'datetime', 'null' => true, 'default' => null],
            'deleted_at'      => ['type' => 'datetime', 'null' => true, 'default' => null],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('ip_address');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('attempts');

        // Rates Table
        $this->forge->addField([
            'id'              => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'default' => null],
            'uri'             => ['type' => 'varchar', 'constraint' => 255, 'null' => false],
            'hour_started_at' => ['type' => 'datetime', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'count'           => ['type' => 'int', 'constraint' => 10, 'null' => false],
            'created_at'      => ['type' => 'datetime', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'      => ['type' => 'datetime', 'null' => true, 'default' => null],
            'deleted_at'      => ['type' => 'datetime', 'null' => true, 'default' => null],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uri');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('rates');

        $this->db->enableForeignKeyChecks();
    }

    public function down(): void
    {
        $this->db->disableForeignKeyChecks();

        $this->forge->dropTable('users', true);
        $this->forge->dropTable('permissions', true);
        $this->forge->dropTable('permissions_users', true);
        $this->forge->dropTable('logins', true);
        $this->forge->dropTable('remember_tokens', true);
        $this->forge->dropTable('identities', true);
        $this->forge->dropTable('groups', true);
        $this->forge->dropTable('permissions_groups', true);
        $this->forge->dropTable('groups_users', true);
        $this->forge->dropTable('logs', true);
        $this->forge->dropTable('apis', true);
        $this->forge->dropTable('controllers', true);
        $this->forge->dropTable('endpoints', true);
        $this->forge->dropTable('attempts', true);
        $this->forge->dropTable('rates', true);

        $this->db->enableForeignKeyChecks();
    }

    private function createTable(string $tableName): void
    {
        $this->forge->createTable($tableName, true);
    }
}
