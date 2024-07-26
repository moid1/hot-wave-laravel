<?php

namespace App\Http\Controllers\Tenancy;

use App\Http\Controllers\Controller;
use App\Tenant;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::where('tenant_id', tenant('id'))->get();
        return view('tenancy.users.index',compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function create()
    {
        return view('tenancy.users.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|unique:users|email:rfc,filter',
            'phone' => [
                'required',
                'unique:users',
                'regex:/^(\+601(0|2|3|4|6|7|8|9)\d{7})$|^(\+601(1)\d{8})$|^(\+65(6|8|9)\d{7})$/'
            ],
            'username' => 'required|unique:users|regex:/^[a-zA-Z0-9]*$/',
        ];

        $customMessages = [
            'username.regex' => 'The :attribute should be lowercase, uppercase, digits without space.'
        ];

        $this->validate($request, $rules, $customMessages);

        $user = new User();
        $user->email = $request->email;
        $user->name = $request->name;
        $user->username = $request->username;
        $user->phone = $request->phone;

        if($request->mailable == 'on'){
            $user->mailable = true;
        }
        if($request->messagable == 'on'){
            $user->messagable = true;
        }

        $user->password = bcrypt(config('app.default_password'));
        $user->tenant()->associate(Tenant::find(\tenant('id')));

        if($request->avatar){
            $user->avatar = $this->saveAvatar($request->avatar, $request->username);
        }

        $user->save();

        return redirect()->route('tenancy.users.index', \tenant('id'))
            ->with(['message' => 'User created successfully.', 'message_type' => 'success']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return view('tenancy.users.show' ,compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        return view('tenancy.users.edit',compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'email' => 'required|email:rfc,filter|unique:users,email,'.$user->id,
            'username' => 'required|regex:/^[a-zA-Z0-9]*$/|unique:users,username,'.$user->id,
            'name' => 'required',
            'phone' => [
                'required',
                'regex:/^(\+601(0|2|3|4|6|7|8|9)\d{7})$|^(\+601(1)\d{8})$|^(\+65(6|8|9)\d{7})$/',
//                'unique:users'
            ],
        ]);

        $user->update($request->all());

        return redirect()->route('tenancy.users.index', \tenant('id'))
            ->with(['message' => 'User updated successfully.', 'message_type' => 'success']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('tenancy.users.index', \tenant('id'))
            ->with(['message' => 'User deleted successfully.', 'message_type' => 'success']);
    }

    /**
     * Save avatar to storage
     * @param $avatar
     * @param $filename
     * @return string
     */
    private function saveAvatar($avatar, $filename){
        $path = 'avatars/' . $filename . '.png';
        Storage::disk(config('voyager.storage.disk'))->put($path, file_get_contents($avatar));
        return $path;
    }
}
