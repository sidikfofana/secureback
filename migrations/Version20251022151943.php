<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251022151943 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        //$this->addSql('ALTER TABLE commentcourriers ADD CONSTRAINT FK_8BF9AE39F675F31B FOREIGN KEY (author_id) REFERENCES User (id) ON DELETE SET NULL');
        //$this->addSql('ALTER TABLE Documents ADD validated_by_id INT DEFAULT NULL, ADD approved_by_id INT DEFAULT NULL, ADD status_validated VARCHAR(255) NOT NULL, ADD date_validated DATETIME DEFAULT NULL, ADD status_approved VARCHAR(255) DEFAULT NULL, ADD date_approved DATETIME DEFAULT NULL');
        //$this->addSql('ALTER TABLE Documents ADD CONSTRAINT FK_2041F02BC69DE5E5 FOREIGN KEY (validated_by_id) REFERENCES User (id)');
        //$this->addSql('ALTER TABLE Documents ADD CONSTRAINT FK_2041F02B2D234F6A FOREIGN KEY (approved_by_id) REFERENCES User (id)');
        //$this->addSql('CREATE INDEX IDX_2041F02BC69DE5E5 ON Documents (validated_by_id)');
        //$this->addSql('CREATE INDEX IDX_2041F02B2D234F6A ON Documents (approved_by_id)');
        //$this->addSql('ALTER TABLE Documents RENAME INDEX idx_2041f02b9d86650f TO IDX_2041F02BA76ED395');
        //$this->addSql('ALTER TABLE Documents RENAME INDEX fk_2041f02b979b1ad6 TO IDX_2041F02B979B1AD6');
        //$this->addSql('ALTER TABLE refresh_tokens CHANGE dtype dtype VARCHAR(255) NOT NULL');
        //$this->addSql('ALTER TABLE user_check_ins DROP user_id, CHANGE check_in_time check_in_time DATETIME NOT NULL, CHANGE check_log check_log JSON DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        //$this->addSql('ALTER TABLE user_check_ins ADD CONSTRAINT FK_DF1F4E3B92508002 FOREIGN KEY (qr_user_id) REFERENCES QRUser (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Documents DROP FOREIGN KEY FK_2041F02BC69DE5E5');
        $this->addSql('ALTER TABLE Documents DROP FOREIGN KEY FK_2041F02B2D234F6A');
        $this->addSql('DROP INDEX IDX_2041F02BC69DE5E5 ON Documents');
        $this->addSql('DROP INDEX IDX_2041F02B2D234F6A ON Documents');
        $this->addSql('ALTER TABLE Documents DROP validated_by_id, DROP approved_by_id, DROP status_validated, DROP date_validated, DROP status_approved, DROP date_approved');
        $this->addSql('ALTER TABLE Documents RENAME INDEX idx_2041f02b979b1ad6 TO FK_2041F02B979B1AD6');
        $this->addSql('ALTER TABLE Documents RENAME INDEX idx_2041f02ba76ed395 TO IDX_2041F02B9D86650F');
        $this->addSql('ALTER TABLE CommentCourriers DROP FOREIGN KEY FK_8BF9AE39F675F31B');
        $this->addSql('ALTER TABLE refresh_tokens CHANGE dtype dtype VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user_check_ins DROP FOREIGN KEY FK_DF1F4E3B92508002');
        $this->addSql('ALTER TABLE user_check_ins ADD user_id INT DEFAULT NULL, CHANGE check_in_time check_in_time DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE check_log check_log LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
    }
}
