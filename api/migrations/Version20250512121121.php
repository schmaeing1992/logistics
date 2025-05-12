<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250512121121 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE api_key (id SERIAL NOT NULL, token_hash VARCHAR(64) NOT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_C912ED9DB3BC57DA ON api_key (token_hash)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE line_schedule (id SERIAL NOT NULL, partner_id UUID NOT NULL, weekday SMALLINT NOT NULL, arrival TIME(0) WITHOUT TIME ZONE NOT NULL, departure TIME(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_895B41819393F8FE ON line_schedule (partner_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN line_schedule.partner_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE package (id UUID NOT NULL, shipment_id UUID NOT NULL, package_number BIGINT NOT NULL, reference VARCHAR(100) DEFAULT NULL, packaging_type VARCHAR(50) NOT NULL, length_cm INT NOT NULL, width_cm INT NOT NULL, height_cm INT NOT NULL, weight_kg DOUBLE PRECISION NOT NULL, volume_weight_kg DOUBLE PRECISION NOT NULL, girth_cm DOUBLE PRECISION NOT NULL, label_base64 TEXT DEFAULT NULL, cancelled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_DE6867957BE036FC ON package (shipment_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN package.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN package.shipment_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE package_status (id UUID NOT NULL, package_id UUID NOT NULL, status_code_id INT NOT NULL, occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, note TEXT DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_45FB16C0F44CABFF ON package_status (package_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_45FB16C02F0A8992 ON package_status (status_code_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN package_status.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN package_status.package_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE partner (id UUID NOT NULL, station_number INT NOT NULL, name VARCHAR(150) NOT NULL, street VARCHAR(255) NOT NULL, postal_code VARCHAR(20) NOT NULL, city VARCHAR(100) NOT NULL, house_number VARCHAR(10) NOT NULL, country VARCHAR(2) NOT NULL, inv_street VARCHAR(255) DEFAULT NULL, inv_postal_code VARCHAR(20) DEFAULT NULL, inv_city VARCHAR(100) DEFAULT NULL, inv_house_number VARCHAR(10) DEFAULT NULL, inv_country VARCHAR(2) DEFAULT NULL, contact_accounting_name VARCHAR(100) DEFAULT NULL, contact_accounting_phone VARCHAR(50) DEFAULT NULL, contact_accounting_email VARCHAR(180) DEFAULT NULL, contact_dispatch_name VARCHAR(100) DEFAULT NULL, contact_dispatch_phone VARCHAR(50) DEFAULT NULL, contact_dispatch_email VARCHAR(180) DEFAULT NULL, phone VARCHAR(50) NOT NULL, emergency_phone VARCHAR(50) DEFAULT NULL, email VARCHAR(180) NOT NULL, delivery_street VARCHAR(255) NOT NULL, delivery_house_number VARCHAR(10) NOT NULL, delivery_postal_code VARCHAR(20) NOT NULL, delivery_city VARCHAR(100) NOT NULL, delivery_country VARCHAR(2) NOT NULL, delivery_phone VARCHAR(50) DEFAULT NULL, delivery_email VARCHAR(180) DEFAULT NULL, opening_hours_warehouse JSON DEFAULT NULL, opening_hours_office JSON DEFAULT NULL, has_forklift BOOLEAN NOT NULL, is_coloader BOOLEAN NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_312B3E1659720CB2 ON partner (station_number)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN partner.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE postal_code_range (id SERIAL NOT NULL, partner_id UUID NOT NULL, country VARCHAR(2) NOT NULL, zip_from VARCHAR(10) NOT NULL, zip_to VARCHAR(10) NOT NULL, "order" SMALLINT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_7731793B9393F8FE ON postal_code_range (partner_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN postal_code_range.partner_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE sequence_counters (name VARCHAR(50) NOT NULL, last_value BIGINT NOT NULL, PRIMARY KEY(name))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE shipment (id UUID NOT NULL, booking_partner_id UUID DEFAULT NULL, pickup_partner_id UUID DEFAULT NULL, delivery_partner_id UUID DEFAULT NULL, tracking_number BIGINT NOT NULL, sender_name1 VARCHAR(100) NOT NULL, sender_name2 VARCHAR(100) DEFAULT NULL, sender_street VARCHAR(255) NOT NULL, sender_postal_code VARCHAR(20) NOT NULL, sender_city VARCHAR(100) NOT NULL, sender_country VARCHAR(100) NOT NULL, sender_email VARCHAR(180) NOT NULL, sender_phone VARCHAR(50) NOT NULL, pickup_note TEXT DEFAULT NULL, pickup_date DATE NOT NULL, pickup_time_from TIME(0) WITHOUT TIME ZONE NOT NULL, pickup_time_to TIME(0) WITHOUT TIME ZONE NOT NULL, pickup_extra_fee DOUBLE PRECISION NOT NULL, recipient_name1 VARCHAR(100) NOT NULL, recipient_name2 VARCHAR(100) DEFAULT NULL, recipient_street VARCHAR(255) NOT NULL, recipient_postal_code VARCHAR(20) NOT NULL, recipient_city VARCHAR(100) NOT NULL, recipient_country VARCHAR(100) NOT NULL, recipient_email VARCHAR(180) NOT NULL, recipient_phone VARCHAR(50) NOT NULL, delivery_note TEXT DEFAULT NULL, delivery_date DATE NOT NULL, delivery_time_from TIME(0) WITHOUT TIME ZONE NOT NULL, delivery_time_to TIME(0) WITHOUT TIME ZONE NOT NULL, delivery_extra_fee DOUBLE PRECISION NOT NULL, customer_reference VARCHAR(255) DEFAULT NULL, internal_document_number VARCHAR(100) DEFAULT NULL, internal_note TEXT DEFAULT NULL, customer_number VARCHAR(50) DEFAULT NULL, order_type VARCHAR(20) NOT NULL, weight_total DOUBLE PRECISION NOT NULL, volume_weight_total DOUBLE PRECISION NOT NULL, girth_max DOUBLE PRECISION NOT NULL, goods_value DOUBLE PRECISION NOT NULL, insurance_value DOUBLE PRECISION NOT NULL, label_base64 TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, cancelled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_2CB20DC3E1C9C18 ON shipment (tracking_number)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2CB20DCA099B8F ON shipment (booking_partner_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2CB20DCC109D0C0 ON shipment (pickup_partner_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2CB20DC8DD66FDA ON shipment (delivery_partner_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN shipment.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN shipment.booking_partner_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN shipment.pickup_partner_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN shipment.delivery_partner_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE status_code (id SERIAL NOT NULL, code VARCHAR(3) NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_4F139D0C77153098 ON status_code (code)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE "user" (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE line_schedule ADD CONSTRAINT FK_895B41819393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE package ADD CONSTRAINT FK_DE6867957BE036FC FOREIGN KEY (shipment_id) REFERENCES shipment (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE package_status ADD CONSTRAINT FK_45FB16C0F44CABFF FOREIGN KEY (package_id) REFERENCES package (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE package_status ADD CONSTRAINT FK_45FB16C02F0A8992 FOREIGN KEY (status_code_id) REFERENCES status_code (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE postal_code_range ADD CONSTRAINT FK_7731793B9393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE shipment ADD CONSTRAINT FK_2CB20DCA099B8F FOREIGN KEY (booking_partner_id) REFERENCES partner (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE shipment ADD CONSTRAINT FK_2CB20DCC109D0C0 FOREIGN KEY (pickup_partner_id) REFERENCES partner (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE shipment ADD CONSTRAINT FK_2CB20DC8DD66FDA FOREIGN KEY (delivery_partner_id) REFERENCES partner (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE line_schedule DROP CONSTRAINT FK_895B41819393F8FE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE package DROP CONSTRAINT FK_DE6867957BE036FC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE package_status DROP CONSTRAINT FK_45FB16C0F44CABFF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE package_status DROP CONSTRAINT FK_45FB16C02F0A8992
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE postal_code_range DROP CONSTRAINT FK_7731793B9393F8FE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE shipment DROP CONSTRAINT FK_2CB20DCA099B8F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE shipment DROP CONSTRAINT FK_2CB20DCC109D0C0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE shipment DROP CONSTRAINT FK_2CB20DC8DD66FDA
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE api_key
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE line_schedule
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE package
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE package_status
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE partner
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE postal_code_range
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE sequence_counters
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE shipment
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE status_code
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE "user"
        SQL);
    }
}
