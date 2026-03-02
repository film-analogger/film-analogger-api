<?php

namespace FilmAnalogger\FilmAnaloggerApi\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;

class KeycloakBearerUser implements UserInterface
{
    private $roles;

    public function __construct(
        private string $sub,
        private string $name,
        private string $email,
        private string $given_name,
        private string $family_name,
        private string $preferred_username,
        private array $attributes,
        private array $token_roles,
        private string $accessToken,
    ) {
        // linéarise et format les roles
        $this->roles = array_merge(
            [],
            array_map(function ($role) {
                return 'ROLE_realm:' . $role;
            }, $token_roles['realm_access']->roles ?? []),
            array_map(function ($role) {
                return 'ROLE_' . $role;
            }, $token_roles['resource_access']->roles ?? []),
            array_merge(
                ...array_map(
                    function ($ressourceName, $value) {
                        return array_map(function ($role) use ($ressourceName) {
                            return 'ROLE_' . $ressourceName . ':' . $role;
                        }, $value->roles);
                    },
                    array_keys($token_roles['external_resource_access']),
                    array_values($token_roles['external_resource_access']),
                ),
            ),
        );
    }

    /**
     * @return string
     */
    public function getSub(): string
    {
        return $this->sub;
    }

    /**
     * @param string $sub
     */
    public function setSub(string $sub): void
    {
        $this->sub = $sub;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * @return string
     */
    public function getGivenName(): string
    {
        return $this->given_name;
    }

    /**
     * @param string $given_name
     */
    public function setGivenName(string $given_name): void
    {
        $this->given_name = $given_name;
    }

    /**
     * @return string
     */
    public function getFamilyName(): string
    {
        return $this->family_name;
    }

    /**
     * @param string $family_name
     */
    public function setFamilyName(string $family_name): void
    {
        $this->family_name = $family_name;
    }

    /**
     * @param string $preferred_username
     */
    public function setPreferredUsername(string $preferred_username): void
    {
        $this->preferred_username = $preferred_username;
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Returns the roles granted to the user.
     *
     *     public function getRoles()
     *     {
     *         return ['ROLE_USER'];
     *     }
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return array (Role|string)[] The user roles
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword()
    {
        // TODO: Implement getPassword() method.
        return $this->sub;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        // TODO: Implement getSalt() method.
        return null;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        // TODO: Implement getUsername() method.
        return $this->preferred_username;
    }

    /**
     * @return string
     */
    public function getUserIdentifier(): string
    {
        return $this->preferred_username;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return array the string representation of the object or null
     */
    public function __serialize(): array
    {
        return [
            $this->sub,
            $this->name,
            $this->email,
            $this->given_name,
            $this->family_name,
            $this->preferred_username,
            $this->roles,
            $this->accessToken,
        ];
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized
     * @return void
     */
    public function __unserialize($serialized): void
    {
        [
            $this->sub,
            $this->name,
            $this->email,
            $this->given_name,
            $this->family_name,
            $this->preferred_username,
            $this->roles,
            $this->accessToken,
        ] = unserialize($serialized, ['allowed_classes' => false]);
    }
}
