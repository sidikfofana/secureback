<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017091641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        //$this->addSql('CREATE TABLE Tasks (id INT AUTO_INCREMENT NOT NULL, project_id_id INT DEFAULT NULL, assigned_to_id INT DEFAULT NULL, parent_task_id_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, priority VARCHAR(255) NOT NULL, start_date DATE DEFAULT NULL, due_date DATE DEFAULT NULL, estimated_time INT DEFAULT NULL, spent_time INT DEFAULT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_91994A936C1197C9 (project_id_id), INDEX IDX_91994A93F4BD7827 (assigned_to_id), INDEX IDX_91994A93FFA30127 (parent_task_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        //$this->addSql('ALTER TABLE Tasks ADD CONSTRAINT FK_91994A936C1197C9 FOREIGN KEY (project_id_id) REFERENCES Project (id)');
        //$this->addSql('ALTER TABLE Tasks ADD CONSTRAINT FK_91994A93F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES User (id)');
        //$this->addSql('ALTER TABLE Tasks ADD CONSTRAINT FK_91994A93FFA30127 FOREIGN KEY (parent_task_id_id) REFERENCES Tasks (id)');
        //$this->addSql('ALTER TABLE user_check_ins DROP user_id, CHANGE check_in_time check_in_time DATETIME NOT NULL, CHANGE check_log check_log JSON DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        //$this->addSql('ALTER TABLE user_check_ins ADD CONSTRAINT FK_DF1F4E3B92508002 FOREIGN KEY (qr_user_id) REFERENCES QRUser (id)');
        $this->addSql('CREATE INDEX IDX_DF1F4E3B92508002 ON user_check_ins (qr_user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Tasks DROP FOREIGN KEY FK_91994A936C1197C9');
        $this->addSql('ALTER TABLE Tasks DROP FOREIGN KEY FK_91994A93F4BD7827');
        $this->addSql('ALTER TABLE Tasks DROP FOREIGN KEY FK_91994A93FFA30127');
        $this->addSql('DROP TABLE Tasks');
        $this->addSql('ALTER TABLE user_check_ins DROP FOREIGN KEY FK_DF1F4E3B92508002');
        $this->addSql('DROP INDEX IDX_DF1F4E3B92508002 ON user_check_ins');
        $this->addSql('ALTER TABLE user_check_ins ADD user_id INT DEFAULT NULL, CHANGE check_in_time check_in_time DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE check_log check_log LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
    }
}
