<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250512124826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP SEQUENCE postal_code_range_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE postal_code_range DROP CONSTRAINT FK_7731793B9393F8FE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE postal_code_range ADD type VARCHAR(10) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE postal_code_range ALTER id TYPE UUID
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE postal_code_range ALTER id DROP DEFAULT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE postal_code_range RENAME COLUMN "order" TO priority
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN postal_code_range.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE postal_code_range ADD CONSTRAINT FK_7731793B9393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE postal_code_range_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE postal_code_range DROP CONSTRAINT fk_7731793b9393f8fe
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE postal_code_range DROP type
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE postal_code_range ALTER id TYPE INT
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE postal_code_range_id_seq
        SQL);
        $this->addSql(<<<'SQL'
            SELECT setval('postal_code_range_id_seq', (SELECT MAX(id) FROM postal_code_range))
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE postal_code_range ALTER id SET DEFAULT nextval('postal_code_range_id_seq')
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE postal_code_range RENAME COLUMN priority TO "order"
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN postal_code_range.id IS NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE postal_code_range ADD CONSTRAINT fk_7731793b9393f8fe FOREIGN KEY (partner_id) REFERENCES partner (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }
}
