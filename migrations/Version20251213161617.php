<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251213161617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE comment (id_comment INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, comment_date DATETIME NOT NULL, id_event INT NOT NULL, id_user INT NOT NULL, INDEX IDX_9474526CD52B4B97 (id_event), INDEX IDX_9474526C6B3CA4B (id_user), PRIMARY KEY (id_comment)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event (id_event INT AUTO_INCREMENT NOT NULL, title VARCHAR(200) NOT NULL, description LONGTEXT DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, location VARCHAR(255) NOT NULL, category VARCHAR(100) NOT NULL, capacity INT NOT NULL, image VARCHAR(255) DEFAULT NULL, id_user INT NOT NULL, INDEX IDX_3BAE0AA76B3CA4B (id_user), PRIMARY KEY (id_event)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE register (id_user INT NOT NULL, id_event INT NOT NULL, INDEX IDX_5FF940146B3CA4B (id_user), INDEX IDX_5FF94014D52B4B97 (id_event), PRIMARY KEY (id_user, id_event)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE registration (id_registration INT AUTO_INCREMENT NOT NULL, registration_date DATETIME NOT NULL, status VARCHAR(50) NOT NULL, PRIMARY KEY (id_registration)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id_user INT AUTO_INCREMENT NOT NULL, last_name VARCHAR(100) NOT NULL, first_name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, photo VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id_user)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CD52B4B97 FOREIGN KEY (id_event) REFERENCES event (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C6B3CA4B FOREIGN KEY (id_user) REFERENCES user (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA76B3CA4B FOREIGN KEY (id_user) REFERENCES user (id)');
        $this->addSql('ALTER TABLE register ADD CONSTRAINT FK_5FF940146B3CA4B FOREIGN KEY (id_user) REFERENCES user (id)');
        $this->addSql('ALTER TABLE register ADD CONSTRAINT FK_5FF94014D52B4B97 FOREIGN KEY (id_event) REFERENCES event (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CD52B4B97');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C6B3CA4B');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA76B3CA4B');
        $this->addSql('ALTER TABLE register DROP FOREIGN KEY FK_5FF940146B3CA4B');
        $this->addSql('ALTER TABLE register DROP FOREIGN KEY FK_5FF94014D52B4B97');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE register');
        $this->addSql('DROP TABLE registration');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
