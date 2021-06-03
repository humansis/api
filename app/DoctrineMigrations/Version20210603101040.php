<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210603101040 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            CREATE TABLE assistance_selection (
                id INT AUTO_INCREMENT NOT NULL,
                threshold INT NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');

        $this->addSql('INSERT INTO assistance_selection (id, threshold) SELECT assistance_id, 0 FROM selection_criteria GROUP BY assistance_id');

        $this->addSql('
            ALTER TABLE assistance
                ADD assistance_selection_id INT NOT NULL,
                ADD CONSTRAINT FK_1B4F85F2E48EFE78 FOREIGN KEY (assistance_selection_id) REFERENCES assistance_selection (id)');

        $this->addSql('UPDATE assistance SET assistance_selection_id=id');

        $this->addSql('
            ALTER TABLE selection_criteria
                ADD assistance_selection_id INT NOT NULL,
                ADD CONSTRAINT FK_61BAEEC9A68DFFCF FOREIGN KEY (assistance_selection_id) REFERENCES assistance_selection (id)');

        $this->addSql('UPDATE selection_criteria SET assistance_selection_id=assistance_id');

        $this->addSql('
            ALTER TABLE selection_criteria
                DROP FOREIGN KEY FK_61BAEEC9A68DFFCF,
                DROP CONSTRAINT FK_61BAEEC97096529A,
                DROP assistance_id');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_1B4F85F2E48EFE78 ON assistance (assistance_selection_id)');
        $this->addSql('CREATE INDEX IDX_61BAEEC9E48EFE78 ON selection_criteria (assistance_selection_id)');
    }

    public function down(Schema $schema): void
    {
    }
}
