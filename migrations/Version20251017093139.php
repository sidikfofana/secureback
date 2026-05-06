<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017093139 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Comments (id INT AUTO_INCREMENT NOT NULL, task_id_id INT DEFAULT NULL, user_id_id INT DEFAULT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A6E8F47CB8E08577 (task_id_id), INDEX IDX_A6E8F47C9D86650F (user_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE TimeEntries (id INT AUTO_INCREMENT NOT NULL, task_id_id INT DEFAULT NULL, user_id_id INT DEFAULT NULL, date DATE NOT NULL, duration NUMERIC(5, 2) NOT NULL, comment LONGTEXT NOT NULL, INDEX IDX_C41CADFBB8E08577 (task_id_id), INDEX IDX_C41CADFB9D86650F (user_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE Comments ADD CONSTRAINT FK_A6E8F47CB8E08577 FOREIGN KEY (task_id_id) REFERENCES Tasks (id)');
        $this->addSql('ALTER TABLE Comments ADD CONSTRAINT FK_A6E8F47C9D86650F FOREIGN KEY (user_id_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE TimeEntries ADD CONSTRAINT FK_C41CADFBB8E08577 FOREIGN KEY (task_id_id) REFERENCES Tasks (id)');
        $this->addSql('ALTER TABLE TimeEntries ADD CONSTRAINT FK_C41CADFB9D86650F FOREIGN KEY (user_id_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE user_check_ins DROP user_id, CHANGE check_in_time check_in_time DATETIME NOT NULL, CHANGE check_log check_log JSON DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user_check_ins ADD CONSTRAINT FK_DF1F4E3B92508002 FOREIGN KEY (qr_user_id) REFERENCES QRUser (id)');
        $this->addSql('CREATE INDEX IDX_DF1F4E3B92508002 ON user_check_ins (qr_user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Comments DROP FOREIGN KEY FK_A6E8F47CB8E08577');
        $this->addSql('ALTER TABLE Comments DROP FOREIGN KEY FK_A6E8F47C9D86650F');
        $this->addSql('ALTER TABLE TimeEntries DROP FOREIGN KEY FK_C41CADFBB8E08577');
        $this->addSql('ALTER TABLE TimeEntries DROP FOREIGN KEY FK_C41CADFB9D86650F');
        $this->addSql('DROP TABLE Comments');
        $this->addSql('DROP TABLE TimeEntries');
        $this->addSql('ALTER TABLE user_check_ins DROP FOREIGN KEY FK_DF1F4E3B92508002');
        $this->addSql('DROP INDEX IDX_DF1F4E3B92508002 ON user_check_ins');
        $this->addSql('ALTER TABLE user_check_ins ADD user_id INT DEFAULT NULL, CHANGE check_in_time check_in_time DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE check_log check_log LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
    }
}
