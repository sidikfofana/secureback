<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251022145543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentcourriers ADD CONSTRAINT FK_8BF9AE39F675F31B FOREIGN KEY (author_id) REFERENCES User (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE Documents ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Documents ADD CONSTRAINT FK_2041F02B979B1AD6 FOREIGN KEY (company_id) REFERENCES Company (id)');
        $this->addSql('CREATE INDEX IDX_2041F02B979B1AD6 ON Documents (company_id)');
        $this->addSql('ALTER TABLE Documents RENAME INDEX idx_2041f02b9d86650f TO IDX_2041F02BA76ED395');
        //$this->addSql('ALTER TABLE refresh_tokens CHANGE dtype dtype VARCHAR(255) NOT NULL');
        //$this->addSql('ALTER TABLE user_check_ins DROP user_id, CHANGE check_in_time check_in_time DATETIME NOT NULL, CHANGE check_log check_log JSON DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        //$this->addSql('ALTER TABLE user_check_ins ADD CONSTRAINT FK_DF1F4E3B92508002 FOREIGN KEY (qr_user_id) REFERENCES QRUser (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Documents DROP FOREIGN KEY FK_2041F02B979B1AD6');
        $this->addSql('DROP INDEX IDX_2041F02B979B1AD6 ON Documents');
        $this->addSql('ALTER TABLE Documents DROP company_id');
        $this->addSql('ALTER TABLE Documents RENAME INDEX idx_2041f02ba76ed395 TO IDX_2041F02B9D86650F');
        $this->addSql('ALTER TABLE CommentCourriers DROP FOREIGN KEY FK_8BF9AE39F675F31B');
        $this->addSql('ALTER TABLE refresh_tokens CHANGE dtype dtype VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user_check_ins DROP FOREIGN KEY FK_DF1F4E3B92508002');
        $this->addSql('ALTER TABLE user_check_ins ADD user_id INT DEFAULT NULL, CHANGE check_in_time check_in_time DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE check_log check_log LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
    }
}
