<?php
declare(strict_types=1);

namespace Kettari\TelegramBundle\Telegram\Subscriber;


use Kettari\TelegramBundle\Entity\Chat;
use Kettari\TelegramBundle\Telegram\Event\GroupCreatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class GroupSubscriber extends AbstractBotSubscriber implements EventSubscriberInterface
{
  /**
   * Returns an array of event names this subscriber wants to listen to.
   *
   * The array keys are event names and the value can be:
   *
   *  * The method name to call (priority defaults to 0)
   *  * An array composed of the method name to call and the priority
   *  * An array of arrays composed of the method names to call and respective
   *    priorities, or 0 if unset
   *
   * For instance:
   *
   *  * array('eventName' => 'methodName')
   *  * array('eventName' => array('methodName', $priority))
   *  * array('eventName' => array(array('methodName1', $priority),
   * array('methodName2')))
   *
   * @return array The event names to listen to
   */
  public static function getSubscribedEvents()
  {
    return [GroupCreatedEvent::NAME => ['onGroupCreated', 90000]];
  }

  /**
   * Handles group creation.
   *
   * @param GroupCreatedEvent $event
   */
  public function onGroupCreated(GroupCreatedEvent $event)
  {
    $this->processGroupCreation($event->getUpdate());

    // Tell the Bot this request is handled
    /*$this->getBot()
      ->setRequestHandled(true);*/
  }

  /**
   * Processes group creation.
   *
   * @param Update $update
   */
  private function processGroupCreation(Update $update)
  {
    // Find chat object. If not found, create new
    $chat = $this->doctrine->getRepository('KettariTelegramBundle:Chat')
      ->findOneByTelegramId($update->message->chat->id);
    if (!$chat) {
      $chat = new Chat();
      $this->doctrine->getManager()
        ->persist($chat);
    }
    $chat->setTelegramId($update->message->chat->id)
      ->setType($update->message->chat->type)
      ->setTitle($update->message->chat->title)
      ->setUsername($update->message->chat->username)
      ->setFirstName($update->message->chat->first_name)
      ->setLastName($update->message->chat->last_name)
      ->setAllMembersAreAdministrators(
        $update->message->chat->all_members_are_administrators
      );

    // Commit changes
    $this->doctrine->getManager()
      ->flush();
  }

}