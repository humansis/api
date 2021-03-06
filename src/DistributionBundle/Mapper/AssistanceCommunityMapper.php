<?php

declare(strict_types=1);

namespace DistributionBundle\Mapper;

use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Mapper\CommunityMapper;
use DistributionBundle\Entity\AssistanceBeneficiary;
use TransactionBundle\Mapper\TransactionMapper;
use VoucherBundle\Mapper\BookletMapper;

class AssistanceCommunityMapper extends AssistanceBeneficiaryMapper
{
    /** @var CommunityMapper */
    private $communityMapper;

    public function __construct(BookletMapper $bookletMapper, GeneralReliefItemMapper $generalReliefItemMapper, TransactionMapper $transactionMapper,
                                CommunityMapper $communityMapper
    ) {
        parent::__construct($bookletMapper, $generalReliefItemMapper, $transactionMapper, null);
        $this->communityMapper = $communityMapper;
    }

    public function toFullArray(?AssistanceBeneficiary $assistanceCommunity): ?array
    {
        if (!$assistanceCommunity) {
            return null;
        }

        $community = $assistanceCommunity->getBeneficiary();
        if (!$community instanceof Community) {
            $class = get_class($community);
            throw new \InvalidArgumentException("AssistanceBeneficiary #{$assistanceCommunity->getId()} is $class instead of ".Community::class);
        }

        $flatBase = $this->toBaseArray($assistanceCommunity);

        return array_merge($flatBase, [
            'community' => $this->communityMapper->toFullArray($community),
        ]);
    }

    public function toFullArrays(iterable $assistanceCommunities): iterable
    {
        foreach ($assistanceCommunities as $assistanceCommunity) {
            yield $this->toFullArray($assistanceCommunity);
        }
    }
}
