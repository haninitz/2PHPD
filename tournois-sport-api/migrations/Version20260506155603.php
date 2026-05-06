<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506155603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE app_user (id INT AUTO_INCREMENT NOT NULL, last_name VARCHAR(100) NOT NULL, first_name VARCHAR(100) NOT NULL, username VARCHAR(64) NOT NULL, email_address VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, UNIQUE INDEX UNIQ_88BDF3E9F85E0677 (username), UNIQUE INDEX UNIQ_88BDF3E9B08E074E (email_address), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, message LONGTEXT NOT NULL, is_read TINYINT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, tournament_id INT DEFAULT NULL, sport_match_id INT DEFAULT NULL, INDEX IDX_BF5476CAA76ED395 (user_id), INDEX IDX_BF5476CA33D1A3E7 (tournament_id), INDEX IDX_BF5476CA1C1C536C (sport_match_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE registration (id INT AUTO_INCREMENT NOT NULL, registration_date DATE NOT NULL, status VARCHAR(20) NOT NULL, player_id INT NOT NULL, tournament_id INT NOT NULL, INDEX IDX_62A8A7A799E6F5DF (player_id), INDEX IDX_62A8A7A733D1A3E7 (tournament_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sport_match (id INT AUTO_INCREMENT NOT NULL, match_date DATE NOT NULL, score_player1 INT DEFAULT NULL, score_player2 INT DEFAULT NULL, status VARCHAR(20) NOT NULL, tournament_id INT NOT NULL, player1_id INT NOT NULL, player2_id INT NOT NULL, INDEX IDX_CE27A41C33D1A3E7 (tournament_id), INDEX IDX_CE27A41CC0990423 (player1_id), INDEX IDX_CE27A41CD22CABCD (player2_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tournament (id INT AUTO_INCREMENT NOT NULL, tournament_name VARCHAR(255) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, location VARCHAR(255) DEFAULT NULL, description LONGTEXT NOT NULL, max_participants INT NOT NULL, sport VARCHAR(100) NOT NULL, organizer_id INT NOT NULL, winner_id INT DEFAULT NULL, INDEX IDX_BD5FB8D9876C4DDA (organizer_id), INDEX IDX_BD5FB8D95DFCD4B8 (winner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA33D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA1C1C536C FOREIGN KEY (sport_match_id) REFERENCES sport_match (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE registration ADD CONSTRAINT FK_62A8A7A799E6F5DF FOREIGN KEY (player_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE registration ADD CONSTRAINT FK_62A8A7A733D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sport_match ADD CONSTRAINT FK_CE27A41C33D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sport_match ADD CONSTRAINT FK_CE27A41CC0990423 FOREIGN KEY (player1_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sport_match ADD CONSTRAINT FK_CE27A41CD22CABCD FOREIGN KEY (player2_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament ADD CONSTRAINT FK_BD5FB8D9876C4DDA FOREIGN KEY (organizer_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament ADD CONSTRAINT FK_BD5FB8D95DFCD4B8 FOREIGN KEY (winner_id) REFERENCES app_user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA33D1A3E7');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA1C1C536C');
        $this->addSql('ALTER TABLE registration DROP FOREIGN KEY FK_62A8A7A799E6F5DF');
        $this->addSql('ALTER TABLE registration DROP FOREIGN KEY FK_62A8A7A733D1A3E7');
        $this->addSql('ALTER TABLE sport_match DROP FOREIGN KEY FK_CE27A41C33D1A3E7');
        $this->addSql('ALTER TABLE sport_match DROP FOREIGN KEY FK_CE27A41CC0990423');
        $this->addSql('ALTER TABLE sport_match DROP FOREIGN KEY FK_CE27A41CD22CABCD');
        $this->addSql('ALTER TABLE tournament DROP FOREIGN KEY FK_BD5FB8D9876C4DDA');
        $this->addSql('ALTER TABLE tournament DROP FOREIGN KEY FK_BD5FB8D95DFCD4B8');
        $this->addSql('DROP TABLE app_user');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE registration');
        $this->addSql('DROP TABLE sport_match');
        $this->addSql('DROP TABLE tournament');
    }
}
