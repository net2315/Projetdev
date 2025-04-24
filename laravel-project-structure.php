/**
 * Structure du projet Laravel pour le système de gestion de stock
 * Voici les principaux fichiers et dossiers qui seront créés
 */

// routes/web.php - Définition des routes
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;

// Routes publiques
Route::get('/', [ProductController::class, 'index'])->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

// Routes d'authentification
Auth::routes();

// Routes protégées pour clients
Route::middleware(['auth'])->group(function () {
    Route::get('/cart', [OrderController::class, 'cart'])->name('cart');
    Route::post('/cart/add', [OrderController::class, 'addToCart'])->name('cart.add');
    Route::delete('/cart/remove/{id}', [OrderController::class, 'removeFromCart'])->name('cart.remove');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
});

// Routes d'administration protégées par authentification et rôle admin
Route::middleware(['auth', 'admin', 'otp'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Gestion des produits
    Route::resource('products', AdminProductController::class);
    
    // Gestion des catégories
    Route::resource('categories', AdminCategoryController::class);
    
    // Gestion des utilisateurs
    Route::resource('users', AdminUserController::class);
    
    // Gestion des commandes
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
});

// app/Models/User.php - Modèle utilisateur
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'otp_secret',
        'otp_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'otp_enabled' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}

// app/Models/Product.php - Modèle produit
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'quantity',
        'category_id',
        'image_url',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}

// app/Models/Category.php - Modèle catégorie
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}

// app/Models/Order.php - Modèle commande
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'total_amount',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}

// app/Models/OrderItem.php - Modèle élément de commande
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price_at_order',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

// app/Http/Controllers/ProductController.php - Contrôleur des produits (public)
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();
        
        // Recherche
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }
        
        // Filtrage par catégorie
        if ($request->has('category')) {
            $query->where('category_id', $request->get('category'));
        }
        
        $products = $query->paginate(12);
        $categories = Category::all();
        
        return view('products.index', compact('products', 'categories'));
    }

    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }
}

// app/Http/Controllers/OrderController.php - Contrôleur des commandes (client)
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function cart()
    {
        $cart = session()->get('cart', []);
        $cartItems = [];
        $total = 0;
        
        foreach ($cart as $id => $details) {
            $product = Product::find($id);
            if ($product) {
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $details['quantity']
                ];
                $total += $product->price * $details['quantity'];
            }
        }
        
        return view('orders.cart', compact('cartItems', 'total'));
    }
    
    public function addToCart(Request $request)
    {
        $id = $request->id;
        $quantity = $request->quantity ?? 1;
        $product = Product::find($id);
        
        if (!$product) {
            return redirect()->back()->with('error', 'Produit non trouvé!');
        }
        
        $cart = session()->get('cart', []);
        
        if (isset($cart[$id])) {
            $cart[$id]['quantity'] += $quantity;
        } else {
            $cart[$id] = [
                'quantity' => $quantity
            ];
        }
        
        session()->put('cart', $cart);
        return redirect()->back()->with('success', 'Produit ajouté au panier!');
    }
    
    public function removeFromCart($id)
    {
        $cart = session()->get('cart', []);
        
        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
        }
        
        return redirect()->back()->with('success', 'Produit retiré du panier!');
    }
    
    public function store(Request $request)
    {
        $cart = session()->get('cart', []);
        
        if (empty($cart)) {
            return redirect()->back()->with('error', 'Votre panier est vide!');
        }
        
        $total = 0;
        foreach ($cart as $id => $details) {
            $product = Product::find($id);
            if ($product) {
                $total += $product->price * $details['quantity'];
            }
        }
        
        $order = Order::create([
            'user_id' => Auth::id(),
            'total_amount' => $total,
            'status' => 'pending'
        ]);
        
        foreach ($cart as $id => $details) {
            $product = Product::find($id);
            if ($product) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $id,
                    'quantity' => $details['quantity'],
                    'price_at_order' => $product->price
                ]);
                
                // Mettre à jour le stock
                $product->quantity -= $details['quantity'];
                $product->save();
            }
        }
        
        // Vider le panier
        session()->forget('cart');
        
        return redirect()->route('orders.show', $order)->with('success', 'Commande passée avec succès!');
    }
    
    public function index()
    {
        $orders = Auth::user()->orders()->orderBy('created_at', 'desc')->paginate(10);
        return view('orders.index', compact('orders'));
    }
    
    public function show(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }
        
        return view('orders.show', compact('order'));
    }
}

// app/Http/Controllers/DashboardController.php - Contrôleur du tableau de bord administrateur
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProducts = Product::count();
        $totalOrders = Order::count();
        $totalUsers = User::where('role', 'client')->count();
        $totalRevenue = Order::where('status', 'completed')->sum('total_amount');
        
        $recentOrders = Order::with('user')->orderBy('created_at', 'desc')->take(5)->get();
        
        $lowStockProducts = Product::where('quantity', '<', 10)->get();
        
        $monthlySales = Order::where('status', 'completed')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('SUM(total_amount) as revenue'))
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        return view('admin.dashboard', compact(
            'totalProducts',
            'totalOrders',
            'totalUsers',
            'totalRevenue',
            'recentOrders',
            'lowStockProducts',
            'monthlySales'
        ));
    }
}

// app/Http/Middleware/AdminMiddleware.php - Middleware pour vérifier le rôle admin
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return $next($request);
        }
        
        return redirect('/')->with('error', 'Accès non autorisé.');
    }
}

// app/Http/Middleware/OtpMiddleware.php - Middleware pour vérifier l'OTP
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class OtpMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        // Si l'utilisateur n'est pas admin ou si l'OTP n'est pas activé, on passe
        if (!$user->isAdmin() || !$user->otp_enabled) {
            return $next($request);
        }
        
        // Si l'OTP est vérifié dans la session, on passe
        if (Session::get('otp_verified')) {
            return $next($request);
        }
        
        // Sinon, on redirige vers la page de vérification OTP
        return redirect()->route('otp.verify');
    }
}

// app/Http/Controllers/OtpController.php - Contrôleur pour la vérification OTP
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use PragmaRX\Google2FA\Google2FA;

class OtpController extends Controller
{
    public function showVerifyForm()
    {
        return view('auth.otp-verify');
    }
    
    public function verify(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric'
        ]);
        
        $user = Auth::user();
        $google2fa = new Google2FA();
        
        $valid = $google2fa->verifyKey($user->otp_secret, $request->otp);
        
        if ($valid) {
            Session::put('otp_verified', true);
            return redirect()->intended('admin/dashboard');
        }
        
        return back()->withErrors(['otp' => 'Code OTP invalide.']);
    }
    
    public function showEnableForm()
    {
        $user = Auth::user();
        
        if ($user->otp_enabled) {
            return redirect()->route('profile.edit')->with('info', 'L\'authentification à deux facteurs est déjà activée.');
        }
        
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );
        
        session(['2fa_secret' => $secret]);
        
        return view('auth.otp-enable', compact('qrCodeUrl', 'secret'));
    }
    
    public function enable(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric'
        ]);
        
        $user = Auth::user();
        $google2fa = new Google2FA();
        $secret = session('2fa_secret');
        
        $valid = $google2fa->verifyKey($secret, $request->otp);
        
        if ($valid) {
            $user->otp_secret = $secret;
            $user->otp_enabled = true;
            $user->save();
            
            session()->forget('2fa_secret');
            
            return redirect()->route('profile.edit')->with('success', 'L\'authentification à deux facteurs a été activée.');
        }
        
        return back()->withErrors(['otp' => 'Code OTP invalide.']);
    }
    
    public function disable(Request $request)
    {
        $user = Auth::user();
        $user->otp_enabled = false;
        $user->save();
        
        return redirect()->route('profile.edit')->with('success', 'L\'authentification à deux facteurs a été désactivée.');
    }
}

// database/migrations/xxxx_xx_xx_create_users_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'client'])->default('client');
            $table->string('otp_secret')->nullable();
            $table->boolean('otp_enabled')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};

// database/migrations/xxxx_xx_xx_create_categories_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('categories');
    }
};

// database/migrations/xxxx_xx_xx_create_products_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('quantity')->default(0);
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};

// database/migrations/xxxx_xx_xx_create_orders_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending', 'processing', 'shipped', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};

// database/migrations/xxxx_xx_xx_create_order_items_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('price_at_order', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_items');
    }
};

// database/seeders/DatabaseSeeder.php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Créer un utilisateur administrateur
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        
        // Créer des utilisateurs clients avec Faker
        \App\Models\User::factory(10)->create();
        
        // Créer des catégories
        $categories = [
            ['name' => 'Électronique', 'description' => 'Produits électroniques et gadgets'],
            ['name' => 'Vêtements', 'description' => 'Vêtements et accessoires de mode'],
            ['name' => 'Livres', 'description' => 'Livres et publications'],
            ['name' => 'Maison & Jardin', 'description' => 'Articles pour la maison et le jardin'],
            ['name' => 'Sports', 'description' => 'Équipements et vêtements de sport'],
        ];
        
        foreach ($categories as $category) {
            Category::create($category);
        }
        
        // Générer des produits avec Faker
        $faker = \Faker\Factory::create();
        
        for ($i = 0; $i < 50; $i++) {
            Product::create([
                'name' => $faker->words(3, true),
                'description' => $faker->paragraph(),
                'price' => $faker->randomFloat(2, 10, 1000),
                'quantity' => $faker->numberBetween(0, 100),
                'category_id' => $faker->numberBetween(1, 5),
                'image_url' => 'https://source.unsplash.com/random/300x200?product=' . $i,
            ]);
        }
        
        // Générer des commandes et articles de commande
        $users = User::where('role', 'client')->get();
        
        foreach ($users as $user) {
            $orderCount = $faker->numberBetween(0, 5);
            
            for ($i = 0; $i < $orderCount; $i++) {
                $status = $faker->randomElement(['pending', 'processing', 'shipped', 'completed', 'cancelled']);
                
                $order = Order::create([
                    'user_id' => $user->id,
                    'total_amount' => 0, // Sera mis à jour après
                    'status' => $status,
                    'created_at' => $faker->dateTimeBetween('-6 months', 'now'),
                ]);
                
                $itemCount = $faker->numberBetween(1, 5);
                $totalAmount = 0;
                
                for ($j = 0; $j < $itemCount; $j++) {
                    $product = Product::inRandomOrder()->first();
                    $quantity = $faker->numberBetween(1, 5);
                    $priceAtOrder = $product->price;
                    
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price_at_order' => $priceAtOrder,
                    ]);
                    
                    $totalAmount += ($priceAtOrder * $quantity);
                }
                
                $order->update(['total_amount' => $totalAmount]);
            }
        }
    }
}
