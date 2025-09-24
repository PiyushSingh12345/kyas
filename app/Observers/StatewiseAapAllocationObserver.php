<?php

namespace App\Observers;

use App\Models\StatewiseAapAllocation;
use App\Models\StatewiseAapAllocationHistory;
use Illuminate\Support\Facades\Auth;

class StatewiseAapAllocationObserver
{
    /**
     * Handle the StatewiseAapAllocation "created" event.
     */
    public function created(StatewiseAapAllocation $statewiseAapAllocation): void
    {
        //
    }

    /**
     * Handle the StatewiseAapAllocation "updated" event.
     */
     /**
     * Handle the "updating" event (before update).
     */
    public function updating(StatewiseAapAllocation $allocation)
    {
        // Ensure we have the original data
        if (!$allocation->wasRecentlyCreated) {
            // Save OLD data before updating
            StatewiseAapAllocationHistory::create([
                'id'              => $allocation->id,
                'financial_year'  => $allocation->getOriginal('financial_year'),
                'state_id'        => $allocation->getOriginal('state_id'),
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
     * Handle the StatewiseAapAllocation "updated" event.
     */
    public function updated(StatewiseAapAllocation $allocation): void
    {
        // Post-update logic can be added here if needed
    }

    /**
     * Handle the StatewiseAapAllocation "deleted" event.
     */
    /**
     * Handle the "deleting" event.
     */
    public function deleting(StatewiseAapAllocation $allocation)
    {
        // Save data before deletion
        StatewiseAapAllocationHistory::create([
            'id'              => $allocation->id,
            'financial_year'  => $allocation->financial_year,
            'state_id'        => $allocation->state_id,
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
     * Handle the StatewiseAapAllocation "restored" event.
     */
    public function restored(StatewiseAapAllocation $statewiseAapAllocation): void
    {
        //
    }

    /**
     * Handle the StatewiseAapAllocation "force deleted" event.
     */
    public function forceDeleted(StatewiseAapAllocation $statewiseAapAllocation): void
    {
        //
    }
}
