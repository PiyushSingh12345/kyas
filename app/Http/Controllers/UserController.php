<?php


namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Auth\Events\Registered;
use App\Http\Requests\StoreUserRequest; 
use App\Http\Requests\UpdateUserRequest;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        // $users = User::select('id', 'name', 'email')->get();
        // return view('users.index', compact('users'));
        return response()->json($users);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    // Store a newly created user in storage
    // skksksarray(7) { 
    //     ["first_name"]=> string(6) "Piyush" 
    //     ["last_name"]=> string(5) "Singh" 
    //     ["designation"]=> string(4) "tect" 
    //     ["mobile"]=> int(8299215118) 
    //     ["email"]=> string(24) "bestdudepiyush@gmail.com" 
    //     ["program_division"]=> string(4) "test" 
    //     ["user_type"]=> array(3) { [0]=> string(6) "Master" [1]=> string(5) "Admin" [2]=> string(2) "KY" } 
    // }
    public function store(Request $request)
    {

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            // 'email' => 'required|email|unique:users',
            'email' => 'required|email',
            // 'password' => 'required|string|min:6|confirmed',
        ]);

        // echo "skksks";var_dump($request->user_type);die;
        // Convert the user_type array to a comma-separated string
        $userTypeString = implode(',', $request->user_type);
        User::create([
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'designation_id' => $request->designation,
            'mobile_number' => $request->mobile,
            'program_division_id' => $request->program_division,
            'user_type_id' => $userTypeString,
            'email' => $validated['email'],
            // 'password' => Hash::make($validated['password']),
            'password' => Hash::make('Test@123'),
        ]);

        // return redirect()->route('users.index')->with('success', 'User created successfully.');
        return redirect()->route('dashboard')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    // public function show(string $id)
    // {
    //     //
    // }

    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    // public function edit(string $id)
    // {
    //     //
    // }

     // Show the form for editing the specified user
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, string $id)
    // {
    //     //
    // }
    
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(string $id)
    // {
    //     //
    // }
    
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }


}
