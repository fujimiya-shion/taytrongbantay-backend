# AGENTS.md

Hướng dẫn bắt buộc cho AI agent khi làm việc trong repo này. Đọc hết trước khi viết code.
Tài liệu cho người: `README.md`.

## 1. Bối cảnh

- Laravel 13 API-only (không Blade, không Inertia, không frontend), PostgreSQL 18, Redis.
- Chia module bằng `nwidart/laravel-modules`. Code nghiệp vụ **chỉ** nằm trong `app/Modules/<Module>`.
- Cấu trúc module do `config/modules.php` và stub trong `stubs/nwidart-stubs/` quyết định.
- Team 5 người, ưu tiên code ít file, dễ đọc. Không thêm tầng trừu tượng ngoài những gì mô tả ở đây.

## 2. Chạy lệnh

Mọi lệnh `php`, `artisan`, `composer` chạy **trong container** với user `sail`:

```bash
docker compose exec -u sail app php artisan <command>
docker compose exec -u sail app composer <command>
docker compose exec -u sail app php artisan test
docker compose exec -u sail app vendor/bin/pint --dirty
```

- Không chạy `artisan` trên máy host: DB host là `pgsql`, chỉ phân giải được trong mạng Docker.
- Không bỏ `-u sail`, nếu không file sinh ra sẽ thuộc root.
- Không sửa `.env`. Biến mới thì thêm vào `.env.example`.

## 3. Bản đồ thư mục

```
app/Core/      Nền tảng gắn với Laravel. KHÔNG sửa nếu không được yêu cầu.
app/Shared/    Base class cho tầng Application. KHÔNG sửa nếu không được yêu cầu.
app/Modules/   Code nghiệp vụ. Mọi tính năng mới viết ở đây.
```

**Cấm** tạo file trong các thư mục mặc định của Laravel: `app/Models`, `app/Http`, `app/Services`, `app/Repositories`, `app/Actions`, `routes/api.php`, `database/migrations`... Không có ngoại lệ.

**Cấm** sửa `vendor/`.

### Class có sẵn để dùng

| Class | Dùng cho |
|---|---|
| `App\Core\Http\Controllers\ApiController` | Base của mọi controller. Có `success($data, $code = 200, $message = 'success')` và `error($code, $message, $errors)` |
| `App\Core\Models\BaseModel` | Base của mọi model |
| `App\Core\Repositories\GenericRepository` | Base của mọi repository: `query`, `get`, `paginate`, `findById`, `first`, `count`, `autoComplete`, `create`, `update`, `delete`, `max` (đều nhận `?QueryCriteria`) |
| `App\Core\Support\Criteria\QueryCriteria` | Điều kiện truy vấn bất biến: `where`, `whereIn`, `whereBetween`, `whereNull`, `with`, `select`, `orderBy`, `paginate`, `perPage`, `limit`, `cursor` |
| `App\Core\DTOs\GenericQueryParams` / `BaseQueryParams` | `::fromRequest($request)->toCriteria(allowedSorts: [...])` |
| `App\Shared\UseCases\UseCase` | Interface đánh dấu, mọi use case implement |
| `App\Shared\Commands\BaseCommand` | Base Command của flow ghi |
| `App\Shared\Commands\QueryCommand` | Base Command của flow đọc, có sẵn `public QueryCriteria $criteria` |
| `App\Shared\Commands\VoidCommand` | Use case không cần input |
| `App\Shared\Outputs\BaseOutput` / `VoidOutput` | Base Output / use case không trả gì |
| `App\Shared\Outputs\PaginationOutput` | Output dạng phân trang, có sẵn `public LengthAwarePaginator $paginator` |
| `App\Shared\Security\PasswordHasher` | `hash`, `matches`, `isHash`. Không gọi thẳng `Hash::` trong use case |

Tìm trong bảng này trước khi viết helper mới. Không tạo bản trùng lặp.

## 4. Cấu trúc một module

```
app/Modules/<Module>/
├── Domain/                              Model, enum, observer
├── Application/
│   ├── Command/                         <Action>Command
│   ├── Output/                          <Action>Output
│   ├── UseCase/                         <Action>UseCase
│   └── Port/Out/                        Interface cho dịch vụ ngoài (hiếm khi cần)
├── Inbound/Rest/V1/
│   ├── Controllers/                     <Resource>Controller
│   ├── Dtos/                            <Action>Request, <Resource>Resource
│   └── Routes/api.php
├── Infrastructure/
│   ├── Persistence/                     <Model>Repository
│   │   └── Migrations/
│   ├── Seeders/
│   └── Providers/
├── Config/config.php
└── Tests/{Feature,Unit}
```

## 5. Flow bắt buộc

**Mọi** endpoint, cả đọc lẫn ghi, đi đúng một flow:

```
Ghi:  Request ─► FormRequest::toCommand() ─────────────────────────► Command ─► UseCase ─► Repository ─► Model
Đọc:  Request ─► QueryParams ─► QueryCriteria ─► new XxxCommand($criteria) ─► UseCase ─► Repository ─► Model
                                                                                  │
Response ◄── $this->success() ◄────────────────────────────────── Output ◄───────┘
```

| Tầng | ĐƯỢC | KHÔNG ĐƯỢC |
|---|---|---|
| Controller | Nhận FormRequest/Request, tạo Command, gọi **đúng 1** use case, trả `$this->success()` / `$this->error()` | Business logic, gọi repository, gọi Eloquent, gọi nhiều use case, `try/catch` nuốt lỗi |
| FormRequest | `rules()`, `authorize()`, `toCommand()` | Truy vấn DB ngoài rule `exists`/`unique` |
| Command / Output | Chỉ chứa dữ liệu, `final readonly` | Method có logic, phụ thuộc `Request` |
| UseCase | Business logic, gọi repository của module mình, gọi use case của module khác, `DB::transaction` | Nhận/trả `Request`/`Response`/`JsonResponse`, query Eloquent trực tiếp (`User::where`...) |
| Repository | Mọi truy vấn DB. Method riêng khi `GenericRepository` không đủ | Business logic, gọi use case |
| Model | Quan hệ, cast, `$fillable`, scope | Business logic phức tạp |

### Những thứ KHÔNG được tạo

- Interface cho use case (`Port/In`, `ICreateUserUseCase`). Controller inject thẳng class use case.
- Interface cho repository (`IUserRepository`). Use case inject thẳng class repository.
- Class Mapper. Request → Command dùng `toCommand()`; Output → JSON dùng `success()` hoặc API Resource.
- Service class (`UserService`), Action class. Logic nằm trong UseCase.
- Facade riêng, helper global, trait mới, base class mới, nếu không được yêu cầu rõ.

Chỉ tạo interface trong `Application/Port/Out` khi tích hợp **dịch vụ bên ngoài** (payment, SMS, storage, API bên thứ ba). Implementation đặt trong `Infrastructure/`, bind trong `<Module>ServiceProvider`.

### Giao tiếp giữa các module

- Module A cần dữ liệu của module B: inject **use case** của B. Không dùng repository hay model của B.
- Quan hệ Eloquent sang model của module khác được phép (FK thì không tránh được), nhưng chỉ để eager-load/hiển thị.

## 6. Sinh code

Luôn dùng generator để file vào đúng chỗ, rồi sửa nội dung:

| Cần | Lệnh | Vị trí |
|---|---|---|
| Module mới | `module:make <Module> --api` | `app/Modules/<Module>` |
| Model | `module:make-model <Model> <Module>` | `Domain/` |
| Enum | `module:make-enum <Name> <Module>` | `Domain/` |
| Migration | `module:make-migration create_<table>_table <Module>` | `Infrastructure/Persistence/Migrations/` |
| Repository | `module:make-repository <Model>Repository <Module>` | `Infrastructure/Persistence/` |
| UseCase | `module:make-service <Action>UseCase <Module>` | `Application/UseCase/` |
| FormRequest | `module:make-request <Action>Request <Module>` | `Inbound/Rest/V1/Dtos/` |
| API Resource | `module:make-resource <Model>Resource <Module>` | `Inbound/Rest/V1/Dtos/` |
| Controller | `module:make-controller <Resource>Controller <Module> --api` | `Inbound/Rest/V1/Controllers/` |
| Seeder | `module:make-seed <Name> <Module>` | `Infrastructure/Seeders/` |
| Test | `module:make-test <Name> <Module> [--feature]` | `Tests/Unit` hoặc `Tests/Feature` |
| Port/Out | `module:make-interface I<Name> <Module>` | `Application/Port/Out/` |

`Command` và `Output` không có generator, tạo file tay trong `Application/Command/` và `Application/Output/`.

## 7. Quy ước đặt tên

- Hành động đặt theo động từ + đối tượng: `CreateUser`, `UpdateUser`, `DeleteUser`, `GetUserDetail`, `GetUserList`, `LoginUser`.
- Một hành động có bộ tên khớp nhau: `CreateUserRequest` → `CreateUserCommand` → `CreateUserUseCase` → `CreateUserOutput`.
- Model số ít (`User`), bảng số nhiều snake_case (`users`), URL số nhiều kebab-case (`/api/v1/user-groups`).
- Route name: `<module>.<resource>.<action>`, ví dụ `identity.users.store`.
- File PHP mới luôn có `declare(strict_types=1);`. Command, Output, UseCase, Repository là `final`.

## 8. Mẫu code

### Ghi

```php
// Application/Command/CreateUserCommand.php
final readonly class CreateUserCommand extends BaseCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}

// Application/Output/CreateUserOutput.php
final readonly class CreateUserOutput extends BaseOutput
{
    public function __construct(public User $user) {}
}

// Inbound/Rest/V1/Dtos/CreateUserRequest.php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email'],
        'password' => ['required', 'string', 'min:8'],
    ];
}

public function toCommand(): CreateUserCommand
{
    return new CreateUserCommand(...$this->validated());
}

// Infrastructure/Persistence/UserRepository.php
final class UserRepository extends GenericRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->first(QueryCriteria::make()->where('email', $email));
    }
}

// Application/UseCase/CreateUserUseCase.php
final class CreateUserUseCase implements UseCase
{
    public function __construct(private readonly UserRepository $users) {}

    public function execute(CreateUserCommand $command): CreateUserOutput
    {
        $user = $this->users->create([
            'name' => $command->name,
            'email' => $command->email,
            'password' => PasswordHasher::hash($command->password),
        ]);

        return new CreateUserOutput($user);
    }
}

// Inbound/Rest/V1/Controllers/UserController.php
public function store(CreateUserRequest $request, CreateUserUseCase $useCase): JsonResponse
{
    $output = $useCase->execute($request->toCommand());

    return $this->success(new UserResource($output->user), 201);
}
```

### Đọc (danh sách)

```php
// Application/Command/GetUserListCommand.php
final readonly class GetUserListCommand extends QueryCommand {}

// Application/Output/GetUserListOutput.php
final readonly class GetUserListOutput extends PaginationOutput {}

// Application/UseCase/GetUserListUseCase.php
public function execute(GetUserListCommand $command): GetUserListOutput
{
    return new GetUserListOutput($this->users->paginate($command->criteria));
}

// Controller
public function index(Request $request, GetUserListUseCase $useCase): JsonResponse
{
    $criteria = GenericQueryParams::fromRequest($request)
        ->toCriteria(allowedSorts: ['name', 'created_at']);

    $output = $useCase->execute(new GetUserListCommand($criteria));

    return $this->success(
        $output->paginator->through(fn (User $user) => new UserResource($user))
    );
}
```

Không dùng `UserResource::collection($paginator)` bên trong `success()`: sẽ mất thông tin phân trang.

Filter riêng của một màn hình: tạo `<Resource>QueryParams extends BaseQueryParams` trong `Inbound/Rest/V1/Dtos/`, khai báo property public, override `applyCustomCriteria()` nếu cần `LIKE` hoặc khoảng ngày.

### Đọc (chi tiết)

```php
final readonly class GetUserDetailCommand extends QueryCommand
{
    public function __construct(public int $id, QueryCriteria $criteria = new QueryCriteria())
    {
        parent::__construct($criteria);
    }
}
```

Không tìm thấy thì use case ném `ModelNotFoundException`; Laravel tự trả 404. Không trả `null` để controller tự check.

### Route

```php
// Inbound/Rest/V1/Routes/api.php — URL cuối cùng: /api/v1/...
Route::prefix('v1')->name('identity.')->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{id}', [UserController::class, 'show'])->name('users.show');
});
```

Không dùng `auth:sanctum`: Sanctum chưa được cài.

## 9. Lỗi và response

- Lỗi validate: để FormRequest tự trả 422.
- Lỗi nghiệp vụ: ném exception trong `Domain/Exceptions/` (`module:make-exception`). Không trả mảng `['success' => false]` từ use case.
- Response thành công luôn qua `$this->success()`. Không dùng `response()->json()` trực tiếp trong controller.
- `error()` hiện có tham số bắt buộc `$errors` đứng sau tham số có default, nên luôn truyền đủ 3 tham số: `$this->error(409, 'Email đã tồn tại', [])`.

## 10. Database

- Migration chỉ tạo bằng `module:make-migration`, đặt trong module sở hữu bảng.
- Không sửa migration đã commit; thay đổi schema thì tạo migration mới.
- Ghi nhiều bảng trong một use case thì bọc `DB::transaction(fn () => ...)` trong use case.
- Chạy: `docker compose exec -u sail app php artisan migrate`.

## 11. Test

- Viết test trong `app/Modules/<Module>/Tests/`, namespace `App\Modules\<Module>\Tests\...`.
- Feature test cho mỗi endpoint mới: case thành công, lỗi validate, lỗi nghiệp vụ.
- Unit test cho use case có logic rẽ nhánh.
- Test dùng database `testing` (cấu hình trong `phpunit.xml`), dùng trait `RefreshDatabase`.
- Chạy test và `pint --dirty` trước khi báo xong.

## 12. Trước khi báo hoàn thành

1. File nằm đúng thư mục theo mục 4 và mục 6, không có file trong thư mục mặc định của Laravel.
2. Controller không chứa logic, use case không chạm `Request`/Eloquent query.
3. Không tạo interface, mapper, service, action ngoài quy định.
4. `docker compose exec -u sail app php artisan test` pass.
5. `docker compose exec -u sail app vendor/bin/pint --dirty` đã chạy.
6. `docker compose exec -u sail app php artisan route:list --path=api` có route mới (nếu thêm endpoint).

## 13. Vấn đề đã biết

- `config/auth.php`, `database/factories/UserFactory.php`, `database/seeders/DatabaseSeeder.php` còn import `App\Models\User` (class đã bị xoá). Không tự sửa; hỏi người dùng nếu task đụng tới auth hoặc seeder.
- Không chạy `composer require laravel/boost` hay `boost:install`: lệnh này ghi đè file này.
- `GenericQueryParams` chưa áp dụng `search`, `fromDate`, `toDate`; `?ids=1,2,3` bị dịch sai thành `id = '2'`; `cursor` không được `GenericRepository::paginate()` dùng. Trong code mới, tự viết điều kiện bằng `whereIn()` / `applyCustomCriteria()`, không dựa vào các field này. Không tự sửa `Core` nếu không được yêu cầu.
