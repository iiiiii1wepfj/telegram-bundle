<?php
/**
 * Created by PhpStorm.
 * User: ant
 * Date: 18.03.2017
 * Time: 0:11
 */

namespace Kaula\TelegramBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="chat")
 */
class Chat {

  /**
   * @ORM\Column(type="bigint")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="NONE")
   */
  private $id;

  /**
   * @ORM\Column(type="string",length=255)
   *
   */
  private $chat_type;

  /**
   * @ORM\Column(type="string",length=255)
   *
   */
  private $title;

  /**
   * @ORM\Column(type="string",length=255)
   *
   */
  private $username;

  /**
   * @ORM\Column(type="string",length=255)
   *
   */
  private $first_name;

  /**
   * @ORM\Column(type="string",length=255)
   *
   */
  private $last_name;

  /**
   * @ORM\Column(type="boolean")
   *
   */
  private $all_members_are_administrators;


    /**
     * Set id
     *
     * @param integer $id
     *
     * @return Chat
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set chatType
     *
     * @param string $chatType
     *
     * @return Chat
     */
    public function setChatType($chatType)
    {
        $this->chat_type = $chatType;

        return $this;
    }

    /**
     * Get chatType
     *
     * @return string
     */
    public function getChatType()
    {
        return $this->chat_type;
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
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
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
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
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
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
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
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
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
     * Get allMembersAreAdministrators
     *
     * @return boolean
     */
    public function getAllMembersAreAdministrators()
    {
        return $this->all_members_are_administrators;
    }
}
