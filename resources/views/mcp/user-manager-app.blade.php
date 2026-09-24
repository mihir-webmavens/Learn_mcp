<x-mcp::app :title="$title">
    <x-slot:head>
        <style>
            :root {
                --color-background-primary: light-dark(#ffffff, #171717);
                --color-background-secondary: light-dark(#f5f5f5, #262626);
                --color-text-primary: light-dark(#171717, #fafafa);
                --color-text-secondary: light-dark(#525252, #a3a3a3);
                --color-border-primary: light-dark(#e5e5e5, #404040);
                --font-sans: system-ui, -apple-system, sans-serif;
                --border-radius-md: 8px;
            }

            body {
                font-family: var(--font-sans);
                background: var(--color-background-primary);
                color: var(--color-text-primary);
                margin: 0;
            }

            [hidden] {
                display: none !important;
            }

            .panel {
                background: var(--color-background-secondary);
                border: 1px solid var(--color-border-primary);
                border-radius: var(--border-radius-md);
            }

            .field {
                width: 100%;
                box-sizing: border-box;
                background: var(--color-background-primary);
                border: 1px solid var(--color-border-primary);
                border-radius: var(--border-radius-md);
                padding: 0.4rem 0.6rem;
                color: inherit;
            }

            .muted {
                color: var(--color-text-secondary);
            }
        </style>

        <script type="module">
            createMcpApp(async (app) => {
                app.autoResize();

                const $ = (id) => document.getElementById(id);

                const state = { search: '', page: 1, lastPage: 1, pendingDelete: null };

                /**
                 * Call a server tool and return its parsed content, throwing the error text on failure.
                 */
                const call = async (name, args = {}) => {
                    const result = await app.callServerTool(name, args);
                    const text = result.content?.[0]?.text ?? '';

                    if (result.isError) {
                        throw new Error(text || 'Something went wrong.');
                    }

                    try {
                        return JSON.parse(text);
                    } catch {
                        return text;
                    }
                };

                const showMessage = (id, text) => {
                    $(id).textContent = text;
                    $(id).hidden = ! text;
                };

                const cell = (text, className = '') => {
                    const td = document.createElement('td');
                    td.className = `px-3 py-2 ${className}`;
                    td.textContent = text;

                    return td;
                };

                const button = (label, className, onClick) => {
                    const el = document.createElement('button');
                    el.type = 'button';
                    el.className = className;
                    el.textContent = label;
                    el.addEventListener('click', onClick);

                    return el;
                };

                const renderUsers = (users) => {
                    const rows = users.map((user) => {
                        const tr = document.createElement('tr');
                        tr.className = 'border-t';
                        tr.style.borderColor = 'var(--color-border-primary)';

                        const actions = cell('', 'text-right whitespace-nowrap');
                        actions.append(
                            button('Edit', 'text-blue-600 hover:underline', () => openForm(user)),
                            button('Delete', 'ml-3 text-red-600 hover:underline', () => requestDelete(user)),
                        );

                        tr.append(cell(user.name), cell(user.email, 'muted'), actions);

                        return tr;
                    });

                    $('rows').replaceChildren(...rows);
                    $('empty').hidden = users.length > 0;
                };

                const load = async (page = state.page) => {
                    showMessage('error', '');

                    try {
                        const data = await call('search-users-tool', { search: state.search, page });

                        Object.assign(state, { page: data.page, lastPage: data.lastPage });

                        renderUsers(data.users);
                        $('total').textContent = `(${data.total})`;
                        $('pager').hidden = data.lastPage <= 1;
                        $('page-label').textContent = `Page ${data.page} of ${data.lastPage}`;
                        $('prev').disabled = data.page <= 1;
                        $('next').disabled = data.page >= data.lastPage;
                    } catch (e) {
                        showMessage('error', e.message);
                    }
                };

                const openForm = (user = null) => {
                    const form = $('user-form');

                    form.reset();
                    form.elements.id.value = user?.id ?? '';
                    form.elements.name.value = user?.name ?? '';
                    form.elements.email.value = user?.email ?? '';
                    form.elements.password.required = ! user;
                    form.elements.password.placeholder = user ? 'Leave blank to keep' : '';
                    $('form-title').textContent = user ? 'Edit user' : 'New user';
                    form.hidden = false;
                };

                const saveUser = async (event) => {
                    event.preventDefault();
                    showMessage('error', '');

                    const args = Object.fromEntries(new FormData(event.target));

                    if (args.id === '') {
                        delete args.id;
                    } else {
                        args.id = Number(args.id);
                    }

                    try {
                        const notice = await call('save-user-tool', args);

                        showMessage('notice', notice);
                        event.target.hidden = true;
                        await load();
                        await app.updateModelContext({ lastAction: notice });
                    } catch (e) {
                        showMessage('error', e.message);
                    }
                };

                const requestDelete = async (user) => {
                    showMessage('error', '');

                    try {
                        const preview = await call('delete-user-tool', { id: user.id });
                        const token = preview.match(/confirmation_token "([^"]+)"/)?.[1];

                        state.pendingDelete = { user, token };
                        $('delete-name').textContent = user.name;
                        $('delete-email').textContent = user.email;
                        $('delete-panel').hidden = false;
                    } catch (e) {
                        showMessage('error', e.message);
                    }
                };

                const confirmDelete = async () => {
                    const { user, token } = state.pendingDelete;

                    try {
                        const notice = await call('delete-user-tool', { id: user.id, confirmation_token: token });

                        showMessage('notice', notice);
                        await load($('rows').children.length === 1 && state.page > 1 ? state.page - 1 : state.page);
                        await app.updateModelContext({ lastAction: notice });
                    } catch (e) {
                        showMessage('error', e.message);
                    } finally {
                        state.pendingDelete = null;
                        $('delete-panel').hidden = true;
                    }
                };

                let searchTimer;

                $('search').addEventListener('input', (event) => {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(() => {
                        state.search = event.target.value;
                        load(1);
                    }, 300);
                });

                $('add-user').addEventListener('click', () => openForm());
                $('cancel-form').addEventListener('click', () => { $('user-form').hidden = true; });
                $('user-form').addEventListener('submit', saveUser);
                $('cancel-delete').addEventListener('click', () => {
                    state.pendingDelete = null;
                    $('delete-panel').hidden = true;
                });
                $('confirm-delete').addEventListener('click', confirmDelete);
                $('prev').addEventListener('click', () => load(state.page - 1));
                $('next').addEventListener('click', () => load(state.page + 1));

                await load(1);
            });
        </script>
    </x-slot:head>

    <div id="app" class="p-4 space-y-4 text-sm">
        <div class="flex items-center justify-between gap-2">
            <h1 class="text-base font-semibold">Users <span id="total" class="muted font-normal"></span></h1>
            <button type="button" id="add-user" class="rounded-md bg-blue-600 px-3 py-1.5 text-white hover:bg-blue-700">Add user</button>
        </div>

        <input type="search" id="search" placeholder="Search by name or email…" class="field">

        <p id="notice" hidden class="rounded-md bg-green-600/10 px-3 py-2 text-green-700 dark:text-green-400"></p>
        <p id="error" hidden class="rounded-md bg-red-600/10 px-3 py-2 text-red-700 dark:text-red-400"></p>

        <form id="user-form" hidden class="panel p-4 space-y-3">
            <h2 id="form-title" class="font-semibold"></h2>
            <input type="hidden" name="id">
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="space-y-1"><span class="muted">Name</span><input class="field" name="name" required></label>
                <label class="space-y-1"><span class="muted">Email</span><input class="field" type="email" name="email" required></label>
                <label class="space-y-1"><span class="muted">Password</span><input class="field" type="password" name="password"></label>
                <label class="space-y-1"><span class="muted">Confirm password</span><input class="field" type="password" name="password_confirmation"></label>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" id="cancel-form" class="rounded-md px-3 py-1.5 muted">Cancel</button>
                <button type="submit" class="rounded-md bg-blue-600 px-3 py-1.5 text-white hover:bg-blue-700">Save</button>
            </div>
        </form>

        <div id="delete-panel" hidden class="panel p-4 space-y-3">
            <p>Permanently delete <strong id="delete-name"></strong> &lt;<span id="delete-email"></span>&gt;? This cannot be undone.</p>
            <div class="flex justify-end gap-2">
                <button type="button" id="cancel-delete" class="rounded-md px-3 py-1.5 muted">Cancel</button>
                <button type="button" id="confirm-delete" class="rounded-md bg-red-600 px-3 py-1.5 text-white hover:bg-red-700">Delete</button>
            </div>
        </div>

        <div class="panel overflow-hidden">
            <table class="w-full text-left">
                <thead class="muted">
                    <tr><th class="px-3 py-2">Name</th><th class="px-3 py-2">Email</th><th class="px-3 py-2"></th></tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
            <p id="empty" hidden class="px-3 py-6 text-center muted">No users found.</p>
        </div>

        <div id="pager" hidden class="flex items-center justify-between muted">
            <button type="button" id="prev" class="disabled:opacity-40">&larr; Previous</button>
            <span id="page-label"></span>
            <button type="button" id="next" class="disabled:opacity-40">Next &rarr;</button>
        </div>
    </div>
</x-mcp::app>
