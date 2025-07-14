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
use App\Models\MdUserType;
use App\Models\MdProgramDivision;
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
    // public function index()
    // {
    //     $users = User::all();
    //     //get all users records with the user_type_id as a comma-separated replace with user_type_name
    //     // $users = User::with('MdUserType:md_user_type_id,user_type_name')->get();

    //     // $users = User::select('id', 'name', 'email')->with('userType:id,user_type_name')->get();
    //     // $users = User::select('id', 'name', 'email')->with('userType')->get();
    //     // $users = User::select('id', 'name', 'email')->get();
    //     // return view('users.index', compact('users'));
    //     return response()->json($users);
    // }

    // public function index()
    // {
    //     // Retrieve all users
    //     $users = User::all();

    //     // Retrieve all user types and map them by their IDs
    //     $userTypes = MdUserType::pluck('user_type_name', 'md_user_type_id');

    //     // Process each user to replace user_type_id with user_type_name(s)
    //     $users->transform(function ($user) use ($userTypes) {
    //         // Split the comma-separated user_type_id into an array
    //         $ids = explode(',', $user->user_type_id);

    //         // Trim any whitespace and filter out empty values
    //         $ids = array_filter(array_map('trim', $ids));

    //         // Map each ID to its corresponding user type name
    //         $names = array_map(function ($id) use ($userTypes) {
    //             return $userTypes[$id] ?? null;
    //         }, $ids);

    //         // Filter out any null values (in case of invalid IDs)
    //         $names = array_filter($names);

    //         // Combine the names into a comma-separated string
    //         $user->user_type = implode(', ', $names);

    //         // Optionally, remove the original user_type_id field
    //         unset($user->user_type_id);

    //         return $user;
    //     });


    //     return response()->json($users);
    // }


    public function index()
{
    // // Retrieve all users
    // $users = User::all();

    // Retrieve all users ordered by id descending
    $users = User::orderBy('id', 'desc')->get();

    // Retrieve all user types and map them by their IDs
    $userTypes = MdUserType::pluck('user_type_name', 'md_user_type_id');

    // Retrieve all program divisions and map them by their IDs
    $programDivisions = MdProgramDivision::pluck('division_name', 'division_id');

    // Process each user to replace IDs with corresponding names
    $users->transform(function ($user) use ($userTypes, $programDivisions) {
        // Process user_type_id (comma-separated IDs)
        $userTypeIds = array_filter(array_map('trim', explode(',', $user->user_type_id)));
        $userTypeNames = array_map(function ($id) use ($userTypes) {
            return $userTypes[$id] ?? null;
        }, $userTypeIds);
        $user->user_type = implode(', ', array_filter($userTypeNames));

        // Process program_division_id
        $user->program_division = $programDivisions[$user->program_division_id] ?? null;

        // Optionally, remove the original ID fields
        // unset($user->user_type_id, $user->program_division_id);

        return $user;
    });

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
            // email should not be unique for create, so we don't include the unique rule here
            'email' => 'required|email',

            // 'email' => 'required|email',
            // 'password' => 'required|string|min:6|confirmed',
        ]);
        
        // Check if the user already exists
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
        // return redirect()->route('dashboard')->with('success', 'User created successfully.');
        // return redirect()->back()->with('success', 'User created successfully.');
        return redirect()->route('user-listing')->with('success', 'User created successfully!');
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
    
    // public function update(Request $request, User $user)
    // {
    //     $validated = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|email|unique:users,email,' . $user->id,
    //         'password' => 'nullable|string|min:6|confirmed',
    //     ]);

    //     $user->name = $validated['name'];
    //     $user->email = $validated['email'];

    //     if (!empty($validated['password'])) {
    //         $user->password = Hash::make($validated['password']);
    //     }

    //     $user->save();

    //     return redirect()->route('users.index')->with('success', 'User updated successfully.');
    // }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(string $id)
    // {
    //     //
    // }
    
    // public function destroy(User $user)
    // {
    //     $user->delete();
    //     return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    // }

    public function update(Request $request, $id)
    {
        // Retrieve the user or fail if not found
        $user = User::findOrFail($id);
// echo "skksks";var_dump($user);die;
        // Validate the incoming request data
        $validatedData = $request->validate([
            'first_name'            => 'required|string|max:255',
            'last_name'             => 'required|string|max:255',
            'designation'        => 'required|string',
            'mobile_number'         => 'required|integer|digits_between:10,15', // Adjust the min and max length as needed
            'program_division_id'   => 'required|integer',
            'user_type_id'          => 'required|array',
            // email should not be unique for update, so we don't include the unique rule here
            'email'                 => 'required|email', // Ensure email is valid, but not unique for the current user  


            // 'email'                 => 'required|email|unique:users,email,' . $user->id, // Uncomment if you want to ensure email uniqueness except for the current user
            // 'password'              => 'nullable|string|min:6|confirmed', // Password can be nullable if not changing
            // Add other validation rules as necessary
        ]);

        // Convert the user_type_id array into a comma-separated string
        $validatedData['user_type_id'] = implode(',', $validatedData['user_type_id']);

        // Update the user with the validated data
    //    echo "assaas"; var_dump($user->update($validatedData)); die;
        $user->update([
            'first_name' => $validatedData['first_name'],
            'last_name' => $validatedData['last_name'],
            'name' => $validatedData['first_name'] . ' ' . $validatedData['last_name'], // Assuming you want to update the name field as well
            'designation_id' => $request->designation, // Assuming you have a designation_id field
            'mobile_number' => $validatedData['mobile_number'],
            'program_division_id' => $validatedData['program_division_id'],
            'user_type_id' => $validatedData['user_type_id'],
            'email' => $validatedData['email'],
            'password' => $request->password ? Hash::make($request->password) : $user->password, // Only update password if provided
        ]);
        // If you need to update the name field as well

        // Redirect back with a success message
        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        // echo "assaas"; print_r($user); die;
        $user->delete();
        // return redirect()->back()->with('success', 'User deleted successfully.');
        return json_encode(['success' => true, 'message' => 'User deleted successfully.']);
        
    }

    /**
     * Return user counts for dashboard blocks.
     */
    public function userCounts()
    {
        $KY_DIVISION_ID = 1;

        $totalUsers = User::count();
        $totalKYUsers = User::where('program_division_id', $KY_DIVISION_ID)->count();
        $totalPDUsers = User::where('program_division_id', '!=', $KY_DIVISION_ID)->count();

        return response()->json([
            'total_users' => $totalUsers,
            'total_pd_users' => $totalPDUsers,
            'total_ky_users' => $totalKYUsers,
        ]);
    }


}
