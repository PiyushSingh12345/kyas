<?php

namespace App\Observers;

use App\Models\PdwiseAapAllocation;
use App\Models\PdWiseAapAllocationHistory;
use Illuminate\Support\Facades\Auth;

class PdWiseAapAllocationObserver
{
    /**
     * Handle the PdwiseAapAllocation "created" event.
     */
    public function created(PdwiseAapAllocation $pdwiseAapAllocation): void
    {
        //
    }

    /**
     * Handle the "updating" event (before update).
     */
    public function updating(PdwiseAapAllocation $allocation)
    {
        // Ensure we have the original data
        if (!$allocation->wasRecentlyCreated) {
            // Save OLD data before updating
            PdWiseAapAllocationHistory::create([
                'id'              => $allocation->id,
                'financial_year'  => $allocation->getOriginal('financial_year'),
                'bh_id'           => $allocation->getOriginal('bh_id'),
                'pd_id'           => $allocation->getOriginal('pd_id'),
                'amount'          => $allocation->getOriginal('amount'),
                'status'          => $allocation->getOriginal('status'),
                'remark'          => $allocation->getOriginal('remark'),
                'created_at'      => $allocation->getOriginal('created_at'),
                'updated_at'      => $allocation->getOriginal('updated_at'),
                'action_type'     => 'UPDATE',
                'changed_by'      => Auth::check() ? Auth::user()->id : 'system',
            ]);
        }
    }

    /**
     * Handle the PdwiseAapAllocation "updated" event.
     */
    public function updated(PdwiseAapAllocation $allocation): void
    {
        // Post-update logic can be added here if needed
    }

    /**
     * Handle the "deleting" event.
     */
    public function deleting(PdwiseAapAllocation $allocation)
    {
        // Save data before deletion
        PdWiseAapAllocationHistory::create([
            'id'              => $allocation->id,
            'financial_year'  => $allocation->financial_year,
            'bh_id'           => $allocation->bh_id,
            'pd_id'           => $allocation->pd_id,
            'amount'          => $allocation->amount,
            'status'          => $allocation->status,
            'remark'          => $allocation->remark,
            'created_at'      => $allocation->created_at,
            'updated_at'      => $allocation->updated_at,
            'action_type'     => 'DELETE',
            'changed_by'      => Auth::check() ? Auth::user()->id : 'system',
        ]);
    }

    /**
     * Handle the PdwiseAapAllocation "restored" event.
     */
    public function restored(PdwiseAapAllocation $pdwiseAapAllocation): void
    {
        //
    }

    /**
     * Handle the PdwiseAapAllocation "force deleted" event.
     */
    public function forceDeleted(PdwiseAapAllocation $pdwiseAapAllocation): void
    {
        //
    }
}
