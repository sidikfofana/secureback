<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241001132858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        /*$this->addSql('ALTER TABLE User ADD CONSTRAINT FK_2DA17977979B1AD6 FOREIGN KEY (company_id) REFERENCES Company (id)');
        $this->addSql('CREATE INDEX IDX_2DA17977979B1AD6 ON User (company_id)');
        $this->addSql('ALTER TABLE Visitors ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Visitors ADD CONSTRAINT FK_8202C669979B1AD6 FOREIGN KEY (company_id) REFERENCES Company (id)');
        $this->addSql('CREATE INDEX IDX_8202C669979B1AD6 ON Visitors (company_id)');*/
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Visitors DROP FOREIGN KEY FK_8202C669979B1AD6');
        $this->addSql('DROP INDEX IDX_8202C669979B1AD6 ON Visitors');
        $this->addSql('ALTER TABLE Visitors DROP company_id');
        $this->addSql('ALTER TABLE User DROP FOREIGN KEY FK_2DA17977979B1AD6');
        $this->addSql('DROP INDEX IDX_2DA17977979B1AD6 ON User');
    }
}
