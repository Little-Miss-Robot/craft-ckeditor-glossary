<?php

namespace littlemissrobot\ckeditorglossary\migrations;

use craft\db\Migration;
use craft\db\Table as CraftTable;

/**
 * Install migration.
 */
class Install extends Migration
{
    public const TABLE = '{{%ckeditorglossary_terms}}';

    public function safeUp(): bool
    {
        if (!$this->db->tableExists(self::TABLE)) {
            $this->createTable(self::TABLE, [
                'id' => $this->integer()->notNull(),
                'siteId' => $this->integer()->notNull(),
                'definition' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'PRIMARY KEY([[id]], [[siteId]])',
            ]);

            $this->createIndex(null, self::TABLE, ['siteId'], false);

            // Definition rows are owned by the element + site — remove them when either goes away.
            $this->addForeignKey(null, self::TABLE, ['id'], CraftTable::ELEMENTS, ['id'], 'CASCADE', null);
            $this->addForeignKey(null, self::TABLE, ['siteId'], CraftTable::SITES, ['id'], 'CASCADE', null);
        }

        return true;
    }

    public function safeDown(): bool
    {
        // Remove any glossary term elements before dropping our table.
        $this->delete(CraftTable::ELEMENTS, ['type' => \littlemissrobot\ckeditorglossary\elements\GlossaryTerm::class]);
        $this->dropTableIfExists(self::TABLE);

        return true;
    }
}
