<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251025212108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        //$this->addSql('ALTER TABLE Project ADD srs_file VARCHAR(255) DEFAULT NULL');
        //$this->addSql('ALTER TABLE commentcourriers ADD CONSTRAINT FK_1E3757E7F675F31B FOREIGN KEY (author_id) REFERENCES User (id) ON DELETE SET NULL');
        //$this->addSql('ALTER TABLE commentcourriers RENAME INDEX idx_8bf9ae398bf41dc7 TO IDX_1E3757E78BF41DC7');
        //$this->addSql('ALTER TABLE commentcourriers RENAME INDEX idx_8bf9ae39f675f31b TO IDX_1E3757E7F675F31B');
        //$this->addSql('ALTER TABLE refresh_tokens CHANGE dtype dtype VARCHAR(255) NOT NULL');
        //$this->addSql('ALTER TABLE user_check_ins DROP user_id, CHANGE check_in_time check_in_time DATETIME NOT NULL, CHANGE check_log check_log JSON DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        //$this->addSql('ALTER TABLE user_check_ins ADD CONSTRAINT FK_DF1F4E3B92508002 FOREIGN KEY (qr_user_id) REFERENCES QRUser (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Project DROP srs_file');
        $this->addSql('ALTER TABLE commentcourriers DROP FOREIGN KEY FK_1E3757E7F675F31B');
        $this->addSql('ALTER TABLE commentcourriers RENAME INDEX idx_1e3757e7f675f31b TO IDX_8BF9AE39F675F31B');
        $this->addSql('ALTER TABLE commentcourriers RENAME INDEX idx_1e3757e78bf41dc7 TO IDX_8BF9AE398BF41DC7');
        $this->addSql('ALTER TABLE refresh_tokens CHANGE dtype dtype VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user_check_ins DROP FOREIGN KEY FK_DF1F4E3B92508002');
        $this->addSql('ALTER TABLE user_check_ins ADD user_id INT DEFAULT NULL, CHANGE check_in_time check_in_time DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE check_log check_log LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
    }
}
