<?php
declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210410230104 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE household ADD settlement_type ENUM(\'nonDisplaceOwnerOccupied\', \'nonDisplaceRentalAccommodation\', \'nonDisplaceInformallyOccupied\', \'displacedDispersedRental\', \'displacedDispersedSpontaneous\', \'displacedCommunalCollectiveCenter\', \'displacedCommunalPlannedCamp\', \'displacedCommunalUnplannedInformalSettlement\') DEFAULT NULL COMMENT \'(DC2Type:enum_settlement_type)\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE household DROP settlement_type');
    }
}
