<?php
/**
 * @author Tormi Talv <tormi.talv@sportlyzer.com> 2022
 * @since 2022-12-12
 * @version 1.0
 */

namespace Infira\MeritAktiva;

class Dimension extends General
{
    public function setDimId(int $dimId)
    {
        $this->set('DimId', $dimId);
    }

    public function setDimValueId(string $dimValueId)
    {
        $this->set('DimValueId', $dimValueId);
    }

    public function setDimCode(string $dimCode)
    {
        $this->set('DimCode', $dimCode);
    }
}