<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Repository;


use Doctrine\ORM\EntityRepository;


/**
 * BotSettingsRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BotSettingsRepository extends EntityRepository
{
  public function findOneByName(string $name)
  {
    return $this->findOneBy(['name' => $name]);
  }
}
