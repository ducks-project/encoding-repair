<?php

/**
 * Part of EncodingRepair package.
 *
 * Example: Custom Type Interpreters and Property Mappers
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use InvalidArgumentException;
use Ducks\Component\EncodingRepair\CharsetProcessor;
use Ducks\Component\EncodingRepair\Interpreter\PropertyMapperInterface;
use Ducks\Component\EncodingRepair\Interpreter\TypeInterpreterInterface;

// Example 1: Custom Property Mapper for User class
class User
{
    public string $name;
    public string $email;
    public string $password; // Should NOT be transcoded
}

class UserMapper implements PropertyMapperInterface
{
    public function map(object $object, callable $transcoder, array $options): object
    {
        if (!$object instanceof User) {
            throw new InvalidArgumentException('Expected User instance');
        }

        $copy = clone $object;
        $copy->name = $transcoder($object->name);
        $copy->email = $transcoder($object->email);
        // password is NOT transcoded for security
        return $copy;
    }
}

// Example 2: Custom Interpreter for Resources
class ResourceInterpreter implements TypeInterpreterInterface
{
    public function supports($data): bool
    {
        return \is_resource($data);
    }

    public function interpret($data, callable $transcoder, array $options)
    {
        $content = \stream_get_contents($data);
        $converted = $transcoder($content);

        $newResource = \fopen('php://memory', 'r+');
        \fwrite($newResource, $converted);
        \rewind($newResource);

        return $newResource;
    }

    public function getPriority(): int
    {
        return 80;
    }
}

// Usage
$processor = new CharsetProcessor();

// Register custom property mapper
$processor->registerPropertyMapper(User::class, new UserMapper());

// Register custom interpreter for resources
$processor->registerInterpreter(new ResourceInterpreter(), 80);

// Example 1: User with custom mapper
$user = new User();
$user->name = \mb_convert_encoding('José', 'ISO-8859-1', 'UTF-8');
$user->email = \mb_convert_encoding('josé@example.com', 'ISO-8859-1', 'UTF-8');
$user->password = \mb_convert_encoding('sécret123', 'ISO-8859-1', 'UTF-8');

$utf8User = $processor->toUtf8($user, CharsetProcessor::ENCODING_ISO);

echo "User name: {$utf8User->name}\n"; // José (converted)
echo "User email: {$utf8User->email}\n"; // josé@example.com (converted)
echo "Password unchanged: " . ($user->password === $utf8User->password ? 'YES' : 'NO') . "\n"; // YES

// Example 2: Resource handling
$resource = \fopen('php://memory', 'r+');
\fwrite($resource, \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8'));
\rewind($resource);

$convertedResource = $processor->toUtf8($resource, CharsetProcessor::ENCODING_ISO);
$content = \stream_get_contents($convertedResource);

echo "Resource content: {$content}\n"; // Café

// Example 3: Complex nested structure
$data = [
    'users' => [
        (object) ['name' => \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8')],
        (object) ['name' => \mb_convert_encoding('Thé', 'ISO-8859-1', 'UTF-8')],
    ],
    'meta' => (object) [
        'title' => \mb_convert_encoding('Données', 'ISO-8859-1', 'UTF-8'),
    ],
];

$converted = $processor->toUtf8($data, CharsetProcessor::ENCODING_ISO);

echo "First user: {$converted['users'][0]->name}\n"; // Café
echo "Meta title: {$converted['meta']->title}\n"; // Données
