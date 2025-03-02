<?php

declare (strict_types=1);
namespace Rector\PHPStanStaticTypeMapper\TypeMapper;

use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use Rector\BetterPhpDocParser\ValueObject\Type\BracketsAwareUnionTypeNode;
use Rector\BetterPhpDocParser\ValueObject\Type\SpacingAwareArrayTypeNode;
use Rector\PHPStanStaticTypeMapper\Contract\TypeMapperInterface;
use Rector\PHPStanStaticTypeMapper\PHPStanStaticTypeMapper;
use Rector\PHPStanStaticTypeMapper\TypeAnalyzer\UnionTypeCommonTypeNarrower;
use Rector\PHPStanStaticTypeMapper\ValueObject\TypeKind;
use RectorPrefix20210828\Symfony\Contracts\Service\Attribute\Required;
/**
 * @see \Rector\Tests\PHPStanStaticTypeMapper\TypeMapper\ArrayTypeMapperTest
 *
 * @implements TypeMapperInterface<ArrayType>
 */
final class ArrayTypeMapper implements \Rector\PHPStanStaticTypeMapper\Contract\TypeMapperInterface
{
    /**
     * @var string
     */
    public const HAS_GENERIC_TYPE_PARENT = 'has_generic_type_parent';
    /**
     * @var \Rector\PHPStanStaticTypeMapper\PHPStanStaticTypeMapper
     */
    private $phpStanStaticTypeMapper;
    /**
     * @var \Rector\PHPStanStaticTypeMapper\TypeAnalyzer\UnionTypeCommonTypeNarrower
     */
    private $unionTypeCommonTypeNarrower;
    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    private $reflectionProvider;
    // To avoid circular dependency
    /**
     * @required
     */
    public function autowireArrayTypeMapper(\Rector\PHPStanStaticTypeMapper\PHPStanStaticTypeMapper $phpStanStaticTypeMapper, \Rector\PHPStanStaticTypeMapper\TypeAnalyzer\UnionTypeCommonTypeNarrower $unionTypeCommonTypeNarrower, \PHPStan\Reflection\ReflectionProvider $reflectionProvider) : void
    {
        $this->phpStanStaticTypeMapper = $phpStanStaticTypeMapper;
        $this->unionTypeCommonTypeNarrower = $unionTypeCommonTypeNarrower;
        $this->reflectionProvider = $reflectionProvider;
    }
    /**
     * @return class-string<Type>
     */
    public function getNodeClass() : string
    {
        return \PHPStan\Type\ArrayType::class;
    }
    /**
     * @param \PHPStan\Type\Type $type
     * @param \Rector\PHPStanStaticTypeMapper\ValueObject\TypeKind $typeKind
     */
    public function mapToPHPStanPhpDocTypeNode($type, $typeKind) : \PHPStan\PhpDocParser\Ast\Type\TypeNode
    {
        $itemType = $type->getItemType();
        if ($itemType instanceof \PHPStan\Type\UnionType && !$type instanceof \PHPStan\Type\Constant\ConstantArrayType) {
            return $this->createArrayTypeNodeFromUnionType($itemType, $typeKind);
        }
        if ($itemType instanceof \PHPStan\Type\ArrayType && $this->isGenericArrayCandidate($itemType)) {
            return $this->createGenericArrayType($type, $typeKind, \true);
        }
        if ($this->isGenericArrayCandidate($type)) {
            return $this->createGenericArrayType($type, $typeKind, \true);
        }
        $narrowedTypeNode = $this->narrowConstantArrayTypeOfUnionType($type, $itemType, $typeKind);
        if ($narrowedTypeNode instanceof \PHPStan\PhpDocParser\Ast\Type\TypeNode) {
            return $narrowedTypeNode;
        }
        $itemTypeNode = $this->phpStanStaticTypeMapper->mapToPHPStanPhpDocTypeNode($itemType, $typeKind);
        return new \Rector\BetterPhpDocParser\ValueObject\Type\SpacingAwareArrayTypeNode($itemTypeNode);
    }
    /**
     * @param \PHPStan\Type\Type $type
     * @param \Rector\PHPStanStaticTypeMapper\ValueObject\TypeKind $typeKind
     */
    public function mapToPhpParserNode($type, $typeKind) : ?\PhpParser\Node
    {
        return new \PhpParser\Node\Name('array');
    }
    private function createArrayTypeNodeFromUnionType(\PHPStan\Type\UnionType $unionType, \Rector\PHPStanStaticTypeMapper\ValueObject\TypeKind $typeKind) : \Rector\BetterPhpDocParser\ValueObject\Type\SpacingAwareArrayTypeNode
    {
        $unionedArrayType = [];
        foreach ($unionType->getTypes() as $unionedType) {
            $typeNode = $this->phpStanStaticTypeMapper->mapToPHPStanPhpDocTypeNode($unionedType, $typeKind);
            $unionedArrayType[(string) $typeNode] = $typeNode;
        }
        if (\count($unionedArrayType) > 1) {
            return new \Rector\BetterPhpDocParser\ValueObject\Type\SpacingAwareArrayTypeNode(new \Rector\BetterPhpDocParser\ValueObject\Type\BracketsAwareUnionTypeNode($unionedArrayType));
        }
        /** @var TypeNode $arrayType */
        $arrayType = \array_shift($unionedArrayType);
        return new \Rector\BetterPhpDocParser\ValueObject\Type\SpacingAwareArrayTypeNode($arrayType);
    }
    private function isGenericArrayCandidate(\PHPStan\Type\ArrayType $arrayType) : bool
    {
        if ($arrayType->getKeyType() instanceof \PHPStan\Type\MixedType) {
            return \false;
        }
        if ($this->isClassStringArrayType($arrayType)) {
            return \true;
        }
        // skip simple arrays, like "string[]", from converting to obvious "array<int, string>"
        if ($this->isIntegerKeyAndNonNestedArray($arrayType)) {
            return \false;
        }
        if ($arrayType->getKeyType() instanceof \PHPStan\Type\NeverType) {
            return \false;
        }
        // make sure the integer key type is not natural/implicit array int keys
        $keysArrayType = $arrayType->getKeysArray();
        if (!$keysArrayType instanceof \PHPStan\Type\Constant\ConstantArrayType) {
            return \true;
        }
        foreach ($keysArrayType->getValueTypes() as $key => $keyType) {
            if (!$keyType instanceof \PHPStan\Type\Constant\ConstantIntegerType) {
                return \true;
            }
            if ($key !== $keyType->getValue()) {
                return \true;
            }
        }
        return \false;
    }
    private function createGenericArrayType(\PHPStan\Type\ArrayType $arrayType, \Rector\PHPStanStaticTypeMapper\ValueObject\TypeKind $typeKind, bool $withKey = \false) : \PHPStan\PhpDocParser\Ast\Type\GenericTypeNode
    {
        $itemTypeNode = $this->phpStanStaticTypeMapper->mapToPHPStanPhpDocTypeNode($arrayType->getItemType(), $typeKind);
        $identifierTypeNode = new \PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode('array');
        // is class-string[] list only
        if ($this->isClassStringArrayType($arrayType)) {
            $withKey = \false;
        }
        if ($withKey) {
            $keyTypeNode = $this->phpStanStaticTypeMapper->mapToPHPStanPhpDocTypeNode($arrayType->getKeyType(), $typeKind);
            $genericTypes = [$keyTypeNode, $itemTypeNode];
        } else {
            $genericTypes = [$itemTypeNode];
        }
        // @see https://github.com/phpstan/phpdoc-parser/blob/98a088b17966bdf6ee25c8a4b634df313d8aa531/tests/PHPStan/Parser/PhpDocParserTest.php#L2692-L2696
        foreach ($genericTypes as $genericType) {
            /** @var \PHPStan\PhpDocParser\Ast\Node $genericType */
            $genericType->setAttribute(self::HAS_GENERIC_TYPE_PARENT, $withKey);
        }
        $identifierTypeNode->setAttribute(self::HAS_GENERIC_TYPE_PARENT, $withKey);
        return new \PHPStan\PhpDocParser\Ast\Type\GenericTypeNode($identifierTypeNode, $genericTypes);
    }
    private function isIntegerKeyAndNonNestedArray(\PHPStan\Type\ArrayType $arrayType) : bool
    {
        if (!$arrayType->getKeyType() instanceof \PHPStan\Type\IntegerType) {
            return \false;
        }
        return !$arrayType->getItemType() instanceof \PHPStan\Type\ArrayType;
    }
    private function narrowConstantArrayTypeOfUnionType(\PHPStan\Type\ArrayType $arrayType, \PHPStan\Type\Type $itemType, \Rector\PHPStanStaticTypeMapper\ValueObject\TypeKind $typeKind) : ?\PHPStan\PhpDocParser\Ast\Type\TypeNode
    {
        if ($arrayType instanceof \PHPStan\Type\Constant\ConstantArrayType && $itemType instanceof \PHPStan\Type\UnionType) {
            $narrowedItemType = $this->unionTypeCommonTypeNarrower->narrowToSharedObjectType($itemType);
            if ($narrowedItemType instanceof \PHPStan\Type\ObjectType) {
                $itemTypeNode = $this->phpStanStaticTypeMapper->mapToPHPStanPhpDocTypeNode($narrowedItemType, $typeKind);
                return new \Rector\BetterPhpDocParser\ValueObject\Type\SpacingAwareArrayTypeNode($itemTypeNode);
            }
            $narrowedItemType = $this->unionTypeCommonTypeNarrower->narrowToGenericClassStringType($itemType);
            if ($narrowedItemType instanceof \PHPStan\Type\Generic\GenericClassStringType) {
                return $this->createTypeNodeFromGenericClassStringType($narrowedItemType, $typeKind);
            }
        }
        return null;
    }
    private function createTypeNodeFromGenericClassStringType(\PHPStan\Type\Generic\GenericClassStringType $genericClassStringType, \Rector\PHPStanStaticTypeMapper\ValueObject\TypeKind $typeKind) : \PHPStan\PhpDocParser\Ast\Type\TypeNode
    {
        $genericType = $genericClassStringType->getGenericType();
        if ($genericType instanceof \PHPStan\Type\ObjectType && !$this->reflectionProvider->hasClass($genericType->getClassName())) {
            return new \PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode($genericType->getClassName());
        }
        $itemTypeNode = $this->phpStanStaticTypeMapper->mapToPHPStanPhpDocTypeNode($genericClassStringType, $typeKind);
        return new \PHPStan\PhpDocParser\Ast\Type\GenericTypeNode(new \PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode('array'), [$itemTypeNode]);
    }
    private function isClassStringArrayType(\PHPStan\Type\ArrayType $arrayType) : bool
    {
        if ($arrayType->getKeyType() instanceof \PHPStan\Type\MixedType) {
            return $arrayType->getItemType() instanceof \PHPStan\Type\Generic\GenericClassStringType;
        }
        if ($arrayType->getKeyType() instanceof \PHPStan\Type\Constant\ConstantIntegerType) {
            return $arrayType->getItemType() instanceof \PHPStan\Type\Generic\GenericClassStringType;
        }
        return \false;
    }
}
