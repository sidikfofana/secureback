<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241129114734 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        /*$this->addSql('CREATE TABLE Company (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, description LONGTEXT NOT NULL, slug VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', logo VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Departements (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, location VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', status TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Evenements (id INT AUTO_INCREMENT NOT NULL, departement_id INT NOT NULL, company_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, location VARCHAR(255) NOT NULL, date_event DATE NOT NULL, time_event TIME NOT NULL, status TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', slug VARCHAR(255) DEFAULT NULL, addressName VARCHAR(255) NOT NULL, INDEX IDX_AE57D7D0CCF9E01E (departement_id), INDEX IDX_AE57D7D0979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE QRCodes (id INT AUTO_INCREMENT NOT NULL, visitor_id INT DEFAULT NULL, code VARCHAR(255) NOT NULL, uidn VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, expiration_date DATE DEFAULT NULL, status TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, isUsed TINYINT(1) DEFAULT NULL, INDEX IDX_BBC68DD570BEE6D (visitor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE QRUser (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, uidn VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, dateExp VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Requests (id INT AUTO_INCREMENT NOT NULL, visitor_id INT DEFAULT NULL, user_id INT DEFAULT NULL, host VARCHAR(255) NOT NULL, status TINYINT(1) DEFAULT NULL, confirmed TINYINT(1) DEFAULT NULL, request_date DATETIME NOT NULL, response_date DATETIME DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_82F3B40770BEE6D (visitor_id), INDEX IDX_82F3B407A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE User (id INT AUTO_INCREMENT NOT NULL, department_id INT NOT NULL, company_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, firstname VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, role LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', title VARCHAR(255) NOT NULL, create_at DATETIME NOT NULL, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', status TINYINT(1) NOT NULL, contact VARCHAR(255) NOT NULL, INDEX IDX_2DA17977AE80F5DF (department_id), INDEX IDX_2DA17977979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Visitors (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, evenements_id INT DEFAULT NULL, company_id INT DEFAULT NULL, firstname VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, contact VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', organisationName VARCHAR(255) DEFAULT NULL, idNumber VARCHAR(255) NOT NULL, visitor_type INT DEFAULT NULL, INDEX IDX_8202C669A76ED395 (user_id), INDEX IDX_8202C66963C02CD4 (evenements_id), INDEX IDX_8202C669979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE check_ins (id INT AUTO_INCREMENT NOT NULL, visitor_id INT DEFAULT NULL, qr_code_id INT DEFAULT NULL, check_in_time DATETIME DEFAULT NULL, check_out_time DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_DFFFC3DF70BEE6D (visitor_id), INDEX IDX_DFFFC3DF12E4AD80 (qr_code_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE Evenements ADD CONSTRAINT FK_AE57D7D0CCF9E01E FOREIGN KEY (departement_id) REFERENCES Departements (id)');
        $this->addSql('ALTER TABLE Evenements ADD CONSTRAINT FK_AE57D7D0979B1AD6 FOREIGN KEY (company_id) REFERENCES Company (id)');
        $this->addSql('ALTER TABLE QRCodes ADD CONSTRAINT FK_BBC68DD570BEE6D FOREIGN KEY (visitor_id) REFERENCES Visitors (id)');
        $this->addSql('ALTER TABLE Requests ADD CONSTRAINT FK_82F3B40770BEE6D FOREIGN KEY (visitor_id) REFERENCES Visitors (id)');
        $this->addSql('ALTER TABLE Requests ADD CONSTRAINT FK_82F3B407A76ED395 FOREIGN KEY (user_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE User ADD CONSTRAINT FK_2DA17977AE80F5DF FOREIGN KEY (department_id) REFERENCES Departements (id)');
        $this->addSql('ALTER TABLE User ADD CONSTRAINT FK_2DA17977979B1AD6 FOREIGN KEY (company_id) REFERENCES Company (id)');
        $this->addSql('ALTER TABLE Visitors ADD CONSTRAINT FK_8202C669A76ED395 FOREIGN KEY (user_id) REFERENCES User (id)');
        $this->addSql('ALTER TABLE Visitors ADD CONSTRAINT FK_8202C66963C02CD4 FOREIGN KEY (evenements_id) REFERENCES Evenements (id)');
        $this->addSql('ALTER TABLE Visitors ADD CONSTRAINT FK_8202C669979B1AD6 FOREIGN KEY (company_id) REFERENCES Company (id)');
        $this->addSql('ALTER TABLE check_ins ADD CONSTRAINT FK_DFFFC3DF70BEE6D FOREIGN KEY (visitor_id) REFERENCES Visitors (id)');
        $this->addSql('ALTER TABLE check_ins ADD CONSTRAINT FK_DFFFC3DF12E4AD80 FOREIGN KEY (qr_code_id) REFERENCES QRCodes (id)');*/
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Evenements DROP FOREIGN KEY FK_AE57D7D0CCF9E01E');
        $this->addSql('ALTER TABLE Evenements DROP FOREIGN KEY FK_AE57D7D0979B1AD6');
        $this->addSql('ALTER TABLE QRCodes DROP FOREIGN KEY FK_BBC68DD570BEE6D');
        $this->addSql('ALTER TABLE Requests DROP FOREIGN KEY FK_82F3B40770BEE6D');
        $this->addSql('ALTER TABLE Requests DROP FOREIGN KEY FK_82F3B407A76ED395');
        $this->addSql('ALTER TABLE User DROP FOREIGN KEY FK_2DA17977AE80F5DF');
        $this->addSql('ALTER TABLE User DROP FOREIGN KEY FK_2DA17977979B1AD6');
        $this->addSql('ALTER TABLE Visitors DROP FOREIGN KEY FK_8202C669A76ED395');
        $this->addSql('ALTER TABLE Visitors DROP FOREIGN KEY FK_8202C66963C02CD4');
        $this->addSql('ALTER TABLE Visitors DROP FOREIGN KEY FK_8202C669979B1AD6');
        $this->addSql('ALTER TABLE check_ins DROP FOREIGN KEY FK_DFFFC3DF70BEE6D');
        $this->addSql('ALTER TABLE check_ins DROP FOREIGN KEY FK_DFFFC3DF12E4AD80');
        $this->addSql('DROP TABLE Company');
        $this->addSql('DROP TABLE Departements');
        $this->addSql('DROP TABLE Evenements');
        $this->addSql('DROP TABLE QRCodes');
        $this->addSql('DROP TABLE QRUser');
        $this->addSql('DROP TABLE Requests');
        $this->addSql('DROP TABLE User');
        $this->addSql('DROP TABLE Visitors');
        $this->addSql('DROP TABLE check_ins');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
