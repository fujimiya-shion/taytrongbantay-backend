# AGENTS.md

Hướng dẫn bắt buộc cho AI agent khi làm việc trong repo này. Đọc hết trước khi viết code.
Tài liệu cho người: `README.md`.

## 1. Bối cảnh

- Laravel 13 API-only (không Blade, không Inertia, không frontend), PostgreSQL 18, Redis.
- Chia module bằng `nwidart/laravel-modules`. Code nghiệp vụ **chỉ** nằm trong `app/Module/<Module>`.
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
app/Module/   Code nghiệp vụ. Mọi tính năng mới viết ở đây.
```

**Cấm** tạo file trong các thư mục mặc định của Laravel: `app/Models`, `app/Http`, `app/Services`, `app/Repositories`, `app/Actions`, `routes/api.php`, `database/migrations`... Không có ngoại lệ.

**Cấm** sửa `vendor/`.

### Class có sẵn để dùng

| Class | Dùng cho |
|---|---|
| `App\Core\Http\Controller\ApiController` | Base của mọi controller. Có `success($data, $code = 200, $message = 'success')` và `error($code, $message, $errors)` |
| `App\Core\Model\BaseModel` | Base của mọi model |
| `App\Core\Repository\ReadRepositoryInterface` | Khả năng đọc chung: `findById`, `first`, `get`, `paginate`, `cursorPaginate`, `count`, `max`, `autoComplete` (đều nhận `?QueryCriteria`). Interface repository của module extends cái này |
| `App\Core\Repository\WriteRepositoryInterface` | Khả năng ghi chung: `create`, `createOrUpdate`, `update`, `delete` |
| `App\Core\Repository\BasePersistenceRepository` | Cài đặt Eloquent của Read + Write, có thêm `query()` chỉ dùng nội bộ. Class cài đặt repository extends cái này |
| `App\Core\Repository\RepositoryDecorator` | Base cho decorator (cache...): chuyển tiếp sẵn mọi method Read/Write xuống `$inner` |
| `App\Core\Support\Criteria\QueryCriteria` | Điều kiện truy vấn bất biến: `where`, `whereIn`, `whereBetween`, `whereNull`, `with`, `select`, `orderBy`, `paginate`, `perPage`, `limit`, `cursor` |
| `App\Core\DTO\GenericQueryParams` / `BaseQueryParams` | `::fromRequest($request)->toCriteria(allowedSorts: [...], allowedRelations: [...])`. Generic đã map `status`, `ids` (IN), `search` (ILIKE cột `name`), `fromDate` / `toDate` (cột `created_at`) |
| `App\Core\Exception\BusinessException` | Base của mọi exception nghiệp vụ; `status()` mặc định 422, `errors()`. `bootstrap/app.php` tự đổi sang JSON |
| `App\Shared\UseCase\UseCase` | Interface đánh dấu, mọi use case implement |
| `App\Shared\Command\BaseCommand` | Base Command của flow ghi |
| `App\Shared\Command\QueryCommand` | Base Command của flow đọc, có sẵn `public QueryCriteria $criteria` |
| `App\Shared\Command\VoidCommand` | Use case không cần input |
| `App\Shared\Output\BaseOutput` / `VoidOutput` | Base Output / use case không trả gì |
| `App\Shared\Output\PaginationOutput` | Output dạng phân trang, có sẵn `public LengthAwarePaginator $paginator` |
| `App\Shared\Output\CursorPaginationOutput` | Output phân trang theo cursor, có sẵn `public CursorPaginator $paginator` (dùng với `cursorPaginate()`) |
| `App\Shared\Security\PasswordHasher` | `hash`, `matches`, `isHash`. Không gọi thẳng `Hash::` trong use case |

Tìm trong bảng này trước khi viết helper mới. Không tạo bản trùng lặp.

## 4. Cấu trúc một module

```
app/Module/<Module>/
├── Domain/
│   ├── Model/                           <Model> (Eloquent)
│   ├── Enum/  Observer/  Exception/  Event/
│   └── Port/Out/
│       └── Persistence/                 <Model>RepositoryInterface
├── Application/
│   ├── Command/                         <Action>Command
│   ├── Output/                          <Action>Output
│   └── UseCase/                         <Action>UseCase
├── Inbound/Rest/V1/
│   ├── Controller/                     <Resource>Controller
│   ├── Dto/                            <Action>Request, <Resource>Resource
│   └── Route/api.php
├── Infrastructure/
│   ├── Persistence/
│   │   ├── Implementation/             <Model>Repository
│   │   └── Migration/
│   ├── Seeder/
│   └── Provider/                       ServiceProvider: bind <Model>RepositoryInterface → <Model>Repository
├── Config/config.php
└── Test/{Feature,Unit}
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
| UseCase | Business logic, gọi repository của module mình **qua interface**, gọi use case của module khác, `DB::transaction` | Nhận/trả `Request`/`Response`/`JsonResponse`, query Eloquent trực tiếp (`User::where`...), inject class cài đặt repository |
| Repository | Mọi truy vấn DB. Method riêng khai báo trong interface khi Read/Write không đủ | Business logic, gọi use case |
| Model | Quan hệ, cast, `$fillable`, scope | Business logic phức tạp |

### Những thứ KHÔNG được tạo

- Interface cho use case (`Port/In`, `ICreateUserUseCase`). Controller inject thẳng class use case.
- Class Mapper. Request → Command dùng `toCommand()`; Output → JSON dùng `success()` hoặc API Resource.
- Service class (`UserService`), Action class. Logic nằm trong UseCase.
- Facade riêng, helper global, trait mới, base class mới, nếu không được yêu cầu rõ.

### Repository: bắt buộc có interface

Mỗi repository gồm 3 phần:

1. **Interface** `Domain/Port/Out/Persistence/<Model>RepositoryInterface.php`:
   - `extends ReadRepositoryInterface, WriteRepositoryInterface` (module chỉ đọc thì chỉ `ReadRepositoryInterface`);
   - **chỉ** khai báo thêm method riêng của module (`findByEmail`...), trả kiểu cụ thể (`?User`, `User`), không trả `?Model`;
   - **cấm** extends `BasePersistenceRepositoryInterface` hoặc khai báo `query()` / trả `Builder`: nơi gọi sẽ query vòng qua repository và qua decorator.
2. **Cài đặt** `Infrastructure/Persistence/Implementation/<Model>Repository.php`: `extends BasePersistenceRepository implements <Model>RepositoryInterface`, constructor nhận model. Bên trong được dùng `query()`.
3. **Binding** trong `<Module>ServiceProvider`: `public array $bindings = [<Model>RepositoryInterface::class => <Model>Repository::class];`

Nơi dùng (use case, seeder) chỉ inject / `app()` **interface**. Cần cache hay log thì viết decorator `extends RepositoryDecorator implements <Model>RepositoryInterface`, chỉ override method cần đổi hành vi và cài method riêng của module, rồi đổi binding. Không tạo decorator nếu không được yêu cầu.

Mẫu có sẵn: module Admin (`AdminRepositoryInterface`, `AdminRepository`, `AdminServiceProvider`).

Interface cho **dịch vụ bên ngoài** (payment, SMS, storage, API bên thứ ba) cũng đặt trong `Domain/Port/Out/<Tên>/`, cài đặt trong `Infrastructure/`, bind trong `<Module>ServiceProvider`.

### Giao tiếp giữa các module

- Module A cần dữ liệu của module B: inject **use case** của B. Không dùng repository hay model của B.
- Quan hệ Eloquent sang model của module khác được phép (FK thì không tránh được), nhưng chỉ để eager-load/hiển thị.

## 6. Sinh code

**Luôn sinh khung bằng lệnh, không tự tạo file tay** (trừ khi không có lệnh nào phù hợp). Sinh xong thì điền vào các dòng `TODO`.

### Ưu tiên 1: `make:feature` cho entity có CRUD

```bash
php artisan make:feature <Entity> <Module>                       # list, show, create, update, delete
php artisan make:feature <Entity> <Module> --actions=list,show   # chỉ các action cần
```

Sinh đủ: model, migration, repository (interface trong `Domain/Port/Out/Persistence/` + cài đặt trong `Infrastructure/Persistence/Implementation/` + binding tự chèn vào `<Module>ServiceProvider`), Command/Output/UseCase cho từng action (use case inject interface), FormRequest, Resource, controller, route (`/api/v1/<entities>`), feature test. Code sinh ra chạy được và test pass ngay. File đã tồn tại thì bị bỏ qua (không dùng `--force` nếu người dùng không yêu cầu).

Sau khi sinh, phải làm hết các `TODO`:
1. Migration: thêm cột.
2. Model: `$fillable`, `$hidden`, quan hệ.
3. Command + FormRequest: field và `rules()`; key của `rules()` trùng tên tham số constructor Command.
4. `<Entity>RepositoryInterface`: chỉ khai báo method riêng (nếu cần), cài đặt trong `<Entity>Repository`.
5. UseCase: map field Command → mảng `create()` / `update()`, thêm logic nghiệp vụ.
6. Resource: field trả ra. Controller: `allowedSorts`.
7. Test: dữ liệu hợp lệ cho cột bắt buộc, thêm case lỗi validate và lỗi nghiệp vụ.

### Ưu tiên 2: `make:action` cho hành động không phải CRUD

```bash
php artisan make:action <VerbObject> <Module>           # vd: PublishPost, ApproveOrder
php artisan make:action <VerbObject> <Module> --query   # hành động đọc, Command extends QueryCommand
```

Sinh `Application/Command/<X>Command`, `Application/Output/<X>Output`, `Application/UseCase/<X>UseCase`. Không thêm hậu tố vào tên. Method controller, route, FormRequest thì tự thêm theo mẫu ở mục 8, vào controller đã có của entity đó.

### Lệnh lẻ cho các loại file còn lại

| Cần | Lệnh | Vị trí |
|---|---|---|
| Module mới | `module:make <Module> --api` | `app/Module/<Module>` |
| Enum | `module:make-enum <Name> <Module>` | `Domain/Enum/` |
| Exception nghiệp vụ | `module:make-exception <Name> <Module>` | `Domain/Exception/` |
| Migration sửa bảng | `module:make-migration add_<col>_to_<table>_table <Module>` | `Infrastructure/Persistence/Migration/` |
| FormRequest | `module:make-request <Action>Request <Module>` | `Inbound/Rest/V1/Dto/` |
| Seeder | `module:make-seed <Name> <Module>` | `Infrastructure/Seeder/` |
| Test | `module:make-test <Name> <Module> [--feature]` | `Test/Unit` hoặc `Test/Feature` |
| Interface dịch vụ ngoài | `module:make-interface <Tên>/<Tên>Interface <Module>` (vd `Payment/PaymentGatewayInterface`) | `Domain/Port/Out/<Tên>/` |
| Repository lẻ (không CRUD) | Không dùng `module:make-repository`; chạy `make:feature <Entity> <Module> --actions=show` rồi xoá phần thừa, hoặc tạo tay đủ interface + cài đặt + binding theo mục 5 | |

Template của `make:feature` / `make:action` nằm ở `stubs/feature/`. Không sửa template nếu không được yêu cầu.

## 7. Quy ước đặt tên

- **Tên thư mục luôn số ít**, trong cả `app/Core`, `app/Shared` lẫn module: `Model/`, `Controller/`, `Dto/`, `Repository/`, `Provider/`, `Migration/`, `Exception/`, `Test/`... Namespace đi theo: `App\Module\<Module>\Inbound\Rest\V1\Controller`. Ngoại lệ: thư mục mặc định của Laravel ở gốc dự án (`tests/`, `routes/`, `database/migrations`...) và tên lớp `QueryCriteria` / thư mục `Support/Criteria`.
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

// Inbound/Rest/V1/Dto/CreateUserRequest.php
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

// Domain/Port/Out/Persistence/UserRepositoryInterface.php
interface UserRepositoryInterface extends ReadRepositoryInterface, WriteRepositoryInterface
{
    public function findByEmail(string $email): ?User;
}

// Infrastructure/Persistence/Implementation/UserRepository.php
final class UserRepository extends BasePersistenceRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        $user = $this->first(QueryCriteria::make()->where('email', $email));

        return $user instanceof User ? $user : null;
    }
}

// Infrastructure/Provider/IdentityServiceProvider.php
public array $bindings = [
    UserRepositoryInterface::class => UserRepository::class,
];

// Application/UseCase/CreateUserUseCase.php
final class CreateUserUseCase implements UseCase
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

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

// Inbound/Rest/V1/Controller/UserController.php
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
        ->toCriteria(allowedSorts: ['name', 'created_at'], allowedRelations: ['roles']);

    $output = $useCase->execute(new GetUserListCommand($criteria));

    return $this->success(
        $output->paginator->through(fn (User $user) => new UserResource($user))
    );
}
```

Không dùng `UserResource::collection($paginator)` bên trong `success()`: sẽ mất thông tin phân trang.

Filter riêng của một màn hình: tạo `<Resource>QueryParams extends BaseQueryParams` trong `Inbound/Rest/V1/Dto/`, khai báo property public, override `applyCustomCriteria()` nếu cần `LIKE` hoặc khoảng ngày.

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
// Inbound/Rest/V1/Route/api.php — URL cuối cùng: /api/v1/...
Route::prefix('v1')->name('identity.')->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{id}', [UserController::class, 'show'])->name('users.show');
});
```

Không dùng `auth:sanctum`: Sanctum chưa được cài.

## 9. Lỗi và response

- Lỗi validate: để FormRequest tự trả 422.
- Lỗi nghiệp vụ: ném exception trong `Domain/Exception/`, **bắt buộc** `extends App\Core\Exception\BusinessException`; override `status()` nếu không phải 422, truyền `errors:` nếu có lỗi theo field. Không tự bắt rồi trả mảng `['success' => false]` từ use case, không render JSON trong exception.
- Response thành công luôn qua `$this->success()`. Không dùng `response()->json()` trực tiếp trong controller.

## 10. Database

- Migration chỉ tạo bằng `module:make-migration`, đặt trong module sở hữu bảng.
- Không sửa migration đã commit; thay đổi schema thì tạo migration mới.
- Ghi nhiều bảng trong một use case thì bọc `DB::transaction(fn () => ...)` trong use case.
- Chạy: `docker compose exec -u sail app php artisan migrate`.

## 11. Test

- Viết test trong `app/Module/<Module>/Test/`, namespace `App\Module\<Module>\Test\...`.
- Feature test cho mỗi endpoint mới: case thành công, lỗi validate, lỗi nghiệp vụ.
- Unit test cho use case có logic rẽ nhánh.
- Test dùng database `testing` (cấu hình trong `phpunit.xml`), dùng trait `RefreshDatabase`.
- Chạy test và `pint --dirty` trước khi báo xong.

## 12. Trước khi báo hoàn thành

1. File nằm đúng thư mục theo mục 4 và mục 6, không có file trong thư mục mặc định của Laravel.
2. Controller không chứa logic, use case không chạm `Request`/Eloquent query.
3. Không tạo interface, mapper, service, action ngoài quy định. Repository có đủ interface + cài đặt + binding; use case inject interface.
4. `docker compose exec -u sail app php artisan test` pass.
5. `docker compose exec -u sail app vendor/bin/pint --dirty` đã chạy.
6. `docker compose exec -u sail app php artisan route:list --path=api` có route mới (nếu thêm endpoint).

## 13. Vấn đề đã biết

- Không chạy `composer require laravel/boost` hay `boost:install`: lệnh này ghi đè file này.
- `?with=` chỉ load quan hệ trong `allowedRelations`; không truyền thì bị bỏ qua. Luôn truyền whitelist khi màn hình cần eager-load.
