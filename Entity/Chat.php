<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 18.03.2017
 * Time: 0:11
 */

namespace Kaula\TelegramBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass="Kaula\TelegramBundle\Repository\ChatRepository")
 * @ORM\Table(name="chat",
 *   options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"})
 */
class Chat
{

  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $id;

  /**
   * @ORM\Column(type="bigint",unique=true)
   */
  private $telegram_id;

  /**
   * @ORM\Column(type="string",length=255)
   *
   */
  private $type;

  /**
   * @ORM\Column(type="string",length=255)
   *
   */
  private $title;

  /**
   * @ORM\Column(type="string",length=255,nullable=true)
   *
   */
  private $username;

  /**
   * @ORM\Column(type="string",length=255,nullable=true)
   *
   */
  private $first_name;

  /**
   * @ORM\Column(type="string",length=255,nullable=true)
   *
   */
  private $last_name;

  /**
   * @ORM\Column(type="boolean")
   *
   */
  private $all_members_are_administrators;

  /**
   * ~~INVERSE SIDE~~
   *
   * @ORM\OneToMany(targetEntity="Kaula\TelegramBundle\Entity\ChatMember",mappedBy="chat")
   */
  private $chat_members;

  /**
   * Get id
   *
   * @return integer
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Get chatType
   *
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }

  /**
   * Set chatType
   *
   * @param string $chatType
   *
   * @return Chat
   */
  public function setType($chatType)
  {
    $this->type = $chatType;

    return $this;
  }

  /**
   * Get title
   *
   * @return string
   */
  public function getTitle()
  {
    return $this->title;
  }

  /**
   * Set title
   *
   * @param string $title
   *
   * @return Chat
   */
  public function setTitle($title)
  {
    $this->title = $title;

    return $this;
  }

  /**
   * Get username
   *
   * @return string
   */
  public function getUsername()
  {
    return $this->username;
  }

  /**
   * Set username
   *
   * @param string $username
   *
   * @return Chat
   */
  public function setUsername($username)
  {
    $this->username = $username;

    return $this;
  }

  /**
   * Get firstName
   *
   * @return string
   */
  public function getFirstName()
  {
    return $this->first_name;
  }

  /**
   * Set firstName
   *
   * @param string $firstName
   *
   * @return Chat
   */
  public function setFirstName($firstName)
  {
    $this->first_name = $firstName;

    return $this;
  }

  /**
   * Get lastName
   *
   * @return string
   */
  public function getLastName()
  {
    return $this->last_name;
  }

  /**
   * Set lastName
   *
   * @param string $lastName
   *
   * @return Chat
   */
  public function setLastName($lastName)
  {
    $this->last_name = $lastName;

    return $this;
  }

  /**
   * Get allMembersAreAdministrators
   *
   * @return boolean
   */
  public function getAllMembersAreAdministrators()
  {
    return $this->all_members_are_administrators;
  }

  /**
   * Set allMembersAreAdministrators
   *
   * @param boolean $allMembersAreAdministrators
   *
   * @return Chat
   */
  public function setAllMembersAreAdministrators($allMembersAreAdministrators)
  {
    $this->all_members_are_administrators = $allMembersAreAdministrators;

    return $this;
  }

  /**
   * Get telegramId
   *
   * @return integer
   */
  public function getTelegramId()
  {
    return $this->telegram_id;
  }

  /**
   * Set telegramId
   *
   * @param integer $telegramId
   *
   * @return Chat
   */
  public function setTelegramId($telegramId)
  {
    $this->telegram_id = $telegramId;

    return $this;
  }
  /**
   * Constructor
   */
  public function __construct()
  {
    $this->chat_members = new ArrayCollection();
  }

  /**
   * Add chatMember
   *
   * @param ChatMember $chatMember
   *
   * @return Chat
   */
  public function addChatMember(ChatMember $chatMember)
  {
    $this->chat_members[] = $chatMember;

    return $this;
  }

  /**
   * Remove chatMember
   *
   * @param ChatMember $chatMember
   */
  public function removeChatMember(ChatMember $chatMember)
  {
    $this->chat_members->removeElement($chatMember);
  }

  /**
   * Get chatMembers
   *
   * @return \Doctrine\Common\Collections\Collection
   */
  public function getChatMembers()
  {
    return $this->chat_members;
  }
}
