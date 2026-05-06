<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017163817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        //$this->addSql('ALTER TABLE Tasks ADD created_by_id INT DEFAULT NULL, CHANGE project_id_id project_id_id INT DEFAULT NULL');
        //$this->addSql('ALTER TABLE Tasks ADD CONSTRAINT FK_91994A93B03A8386 FOREIGN KEY (created_by_id) REFERENCES User (id)');
        //$this->addSql('CREATE INDEX IDX_91994A93B03A8386 ON Tasks (created_by_id)');
        //$this->addSql('ALTER TABLE refresh_tokens ADD dtype VARCHAR(255) NOT NULL');
        //$this->addSql('ALTER TABLE user_check_ins DROP user_id, CHANGE check_in_time check_in_time DATETIME NOT NULL, CHANGE check_log check_log JSON DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        //$this->addSql('ALTER TABLE user_check_ins ADD CONSTRAINT FK_DF1F4E3B92508002 FOREIGN KEY (qr_user_id) REFERENCES QRUser (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Tasks DROP FOREIGN KEY FK_91994A93B03A8386');
        $this->addSql('DROP INDEX IDX_91994A93B03A8386 ON Tasks');
        $this->addSql('ALTER TABLE Tasks DROP created_by_id, CHANGE project_id_id project_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE refresh_tokens DROP dtype');
        $this->addSql('ALTER TABLE user_check_ins DROP FOREIGN KEY FK_DF1F4E3B92508002');
        $this->addSql('ALTER TABLE user_check_ins ADD user_id INT DEFAULT NULL, CHANGE check_in_time check_in_time DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE check_log check_log LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
    }
}
