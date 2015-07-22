<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShameRequest;
use App\Shame;
use App\Tag;
use Auth;

use App\Http\Requests;
use Carbon\Carbon;

class ShameController extends Controller
{
    /**
     * Instantiate a new ShamesController instance.
     */
    public function __construct()
    {
        $this->middleware('auth', ['only' => ['create', 'store']]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return $this
     */
    public function create()
    {
        $tags = [];
        foreach (Tag::all() as $tag) {
            array_push($tags, $tag->title);
        }

        return view('shame.create')->with([
            'js_tags' => json_encode($tags)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreShameRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreShameRequest $request)
    {
        $shame = new Shame;
        $shame->title = $request->title;
        $shame->markdown = $request->markdown;
        $shame->is_anonymous = $request->is_anonymous;
        $shame->user_id = Auth::user()->id;
        $shame->save();

        $tag_titles = explode(', ', $request->tags);
        foreach ($tag_titles as $tag_title) {
            $tag = Tag::where('title', $tag_title)->first();

            if ($tag) {
                $shame->tags()->attach($tag);
            }
        }

        return redirect()->route('home');
    }

    /**
     * Display a listing of shames with the most upvotes in the last 24 hours.
     *
     * @return $this
     */
    public function featuredShames()
    {
        $shames = Shame::where('created_at', '>=', Carbon::now()
            ->subHour(24))
            ->with('user','tags','upvotes')
            ->get()
            ->sortByDesc(function($shame)
            {
                return $shame->upvotes->count();
            })
            ->take(10);
        $title = 'Featured Shames';
        return view('shame.index')->with(['shames' => $shames, 'title' => $title]);
    }

    /**
     * Display a listing of the shames with the most upvotes overall.
     *
     * @return $this
     */
    public function topShames()
    {
        $shames = Shame::with('user','tags','upvotes')
            ->get()
            ->sortByDesc(function($shame){
                return $shame->upvotes->count();
            })
            ->take(10);
        $title = 'Top Shames';
        return view('shame.index')->with(['shames' => $shames, 'title' => $title]);
    }

    /**
     * Display a listing of the newest shames.
     *
     * @return $this
     */
    public function newShames()
    {
        $shames = Shame::with('user','tags','upvotes')
            ->orderby('created_at', 'asc')
            ->get()
            ->take(10);
        $title = 'New Shames';
        return view('shame.index')->with(['shames' => $shames, 'title' => $title]);
    }

    /**
     * Display a listing of random shames.
     *
     * @return $this
     */
    public function randomShames()
    {
        $shames = Shame::with('user','tags','upvotes')
            ->orderByRaw("RAND()")
            ->get()
            ->take(10);
        $title = 'Random Shames';
        return view('shame.index')->with(['shames' => $shames, 'title' => $title]);
    }
}