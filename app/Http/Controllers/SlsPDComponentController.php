<?php 
namespace App\Http\Controllers;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use App\Models\SlsPDComponent;
class SlsPDComponentController extends Controller
{

    public function index()
    {
        $data = SlsPDComponent::with('state')->get();

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'component' => 'required|in:PD,SL',
            'comValue' => 'required|array',
            'comValue.*.state' => 'required|integer',
            'comValue.*.name' => 'required|string',
            'comValue.*.slsPD' => 'nullable|string',
            'status' => 'required|in:0,1'
        ]);

        foreach ($validated['comValue'] as $entry) {
            SlsPDComponent::create([
                'component' => $validated['component'],
                'state_id' => $entry['state'],
                'slsPD' => $entry['slsPD'] ?? null,
                'name' => $entry['name'],
                'status' => $validated['status']
            ]);
        }

        return redirect()->back()->with('success', 'Data stored successfully!');
    }

    public function getComponentsByFund(Request $request)
    {
        $fund = $request->get('fund');
        $stateId = $request->get('state_id'); // Get state_id if provided

        if ($fund == '2435') {
            $components = SlsPDComponent::with('state')
                ->where('component', 'PD')
                ->get();
        } elseif (in_array($fund, ['3601', '3602', '2552'])) {
            if (!$stateId) {
                return response()->json(['error' => 'State ID required for this fund'], 422);
            }

            $components = SlsPDComponent::with('state')
                ->where('component', 'SL')
                ->where('state_id', $stateId)
                ->get();
        } else {
            return response()->json([], 400);
        }

        return response()->json($components);
    }


}

