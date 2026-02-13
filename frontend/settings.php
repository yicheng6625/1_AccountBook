<?php
$pageTitle = '設定';
$currentPage = 'settings';
include __DIR__ . '/components/header.php';
?>

<!-- 快捷新增管理 -->
<div style="padding:12px 16px;font-size:13px;color:#999;background:#f9f9f9;border-bottom:1px solid #eee;">
    快捷新增管理
</div>

<div id="quickadd-list"></div>

<button class="btn btn-primary" id="btn-add-quickadd">+ 新增快捷範本</button>

<!-- 分類管理 -->
<div style="padding:12px 16px;font-size:13px;color:#999;background:#f9f9f9;border-bottom:1px solid #eee;">
    分類管理
</div>

<div id="category-list"></div>

<button class="btn btn-primary" id="btn-add-category">+ 新增分類</button>

<!-- 分類彈窗 -->
<div class="modal-overlay" id="modal">
    <div class="modal-box">
        <h3 id="modal-title">新增分類</h3>
        <input type="text" id="modal-name" placeholder="分類名稱">
        <div class="modal-actions">
            <button class="btn-cancel" id="modal-cancel">取消</button>
            <button class="btn-confirm" id="modal-confirm">確定</button>
        </div>
    </div>
</div>

<!-- 快捷新增彈窗 -->
<div class="modal-overlay" id="qa-modal">
    <div class="modal-box" style="width:340px;text-align:left;">
        <h3 id="qa-modal-title" style="text-align:center;">新增快捷範本</h3>

        <div class="qa-form-group">
            <label>範本名稱</label>
            <input type="text" id="qa-name" placeholder="例：早餐、月租轉帳">
        </div>

        <div class="qa-form-group">
            <label>類型</label>
            <select id="qa-template-type">
                <option value="record">記帳</option>
                <option value="transfer">轉帳</option>
            </select>
        </div>

        <!-- 記帳欄位 -->
        <div id="qa-record-fields">
            <div class="qa-form-group">
                <label>帳戶</label>
                <select id="qa-account"></select>
            </div>
            <div class="qa-form-group">
                <label>收支類型</label>
                <select id="qa-type">
                    <option value="支出">支出</option>
                    <option value="收入">收入</option>
                </select>
            </div>
            <div class="qa-form-group">
                <label>金額</label>
                <input type="number" id="qa-amount" step="1" min="0" placeholder="0">
            </div>
            <div class="qa-form-group">
                <label>項目名稱</label>
                <input type="text" id="qa-item" placeholder="例：午餐">
            </div>
            <div class="qa-form-group">
                <label>分類</label>
                <select id="qa-category"></select>
            </div>
            <div class="qa-form-group">
                <label>備註</label>
                <input type="text" id="qa-note" placeholder="選填">
            </div>
        </div>

        <!-- 轉帳欄位 -->
        <div id="qa-transfer-fields" style="display:none;">
            <div class="qa-form-group">
                <label>轉出帳戶</label>
                <select id="qa-from-account"></select>
            </div>
            <div class="qa-form-group">
                <label>轉入帳戶</label>
                <select id="qa-to-account"></select>
            </div>
            <div class="qa-form-group">
                <label>金額</label>
                <input type="number" id="qa-transfer-amount" step="1" min="0" placeholder="0">
            </div>
            <div class="qa-form-group">
                <label>備註</label>
                <input type="text" id="qa-transfer-note" placeholder="選填">
            </div>
        </div>

        <div class="modal-actions" style="margin-top:12px;">
            <button class="btn-cancel" id="qa-modal-cancel">取消</button>
            <button class="btn-confirm" id="qa-modal-confirm">確定</button>
        </div>
    </div>
</div>

<style>
.qa-form-group {
    margin-bottom: 10px;
}
.qa-form-group label {
    display: block;
    font-size: 12px;
    color: #999;
    margin-bottom: 3px;
}
.qa-form-group input,
.qa-form-group select {
    width: 100%;
    padding: 7px 8px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 13px;
    outline: none;
}
.quickadd-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 16px;
    border-bottom: 1px solid #f0f0f0;
}
.quickadd-info {
    flex: 1;
    min-width: 0;
}
.quickadd-name {
    font-size: 14px;
    font-weight: 500;
}
.quickadd-detail {
    font-size: 11px;
    color: #999;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.quickadd-actions {
    display: flex;
    gap: 6px;
    flex-shrink: 0;
    margin-left: 8px;
}
.quickadd-actions button {
    padding: 4px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
    font-size: 12px;
    cursor: pointer;
}
.quickadd-actions .btn-del {
    color: #e74c3c;
    border-color: #e74c3c;
}
</style>

<script>
    let editingId = null;
    let qaEditingId = null;
    let accountsCache = [];
    let categoriesCache = [];

    // ========== 快捷新增管理 ==========

    async function loadQuickAddTemplates() {
        try {
            const templates = await API.getQuickAddTemplates();
            const listEl = document.getElementById('quickadd-list');

            if (!templates || templates.length === 0) {
                listEl.innerHTML = '<div class="empty-message">尚無快捷範本</div>';
                return;
            }

            listEl.innerHTML = templates.map(t => {
                let detail = '';
                if (t.template_type === 'transfer') {
                    detail = `轉帳：${escapeHtml(t.account_name)} → ${escapeHtml(t.to_account_name)}　$${t.amount}`;
                } else {
                    detail = `${t.type}：${escapeHtml(t.item)}　$${t.amount}　${escapeHtml(t.category_name)}　${escapeHtml(t.account_name)}`;
                }
                return `
                <div class="quickadd-item" data-id="${t.id}">
                    <div class="quickadd-info">
                        <div class="quickadd-name">${escapeHtml(t.name)}</div>
                        <div class="quickadd-detail">${detail}</div>
                    </div>
                    <div class="quickadd-actions">
                        <button onclick="editQuickAdd(${t.id})">編輯</button>
                        <button class="btn-del" onclick="deleteQuickAdd(${t.id}, '${escapeAttr(t.name)}')">刪除</button>
                    </div>
                </div>`;
            }).join('');
        } catch (e) {
            showToast('載入快捷範本失敗');
        }
    }

    async function loadFormOptions() {
        try {
            const [accounts, categories] = await Promise.all([
                API.getAccounts(),
                API.getCategories(),
            ]);
            accountsCache = accounts || [];
            categoriesCache = categories || [];

            populateSelect('qa-account', accountsCache);
            populateSelect('qa-from-account', accountsCache);
            populateSelect('qa-to-account', accountsCache);
            populateSelect('qa-category', categoriesCache);
        } catch (e) {
            showToast('載入選單資料失敗');
        }
    }

    function populateSelect(selectId, items) {
        const sel = document.getElementById(selectId);
        sel.innerHTML = '';
        items.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.name;
            sel.appendChild(opt);
        });
    }

    // 切換記帳/轉帳欄位
    document.getElementById('qa-template-type').addEventListener('change', (e) => {
        const isTransfer = e.target.value === 'transfer';
        document.getElementById('qa-record-fields').style.display = isTransfer ? 'none' : 'block';
        document.getElementById('qa-transfer-fields').style.display = isTransfer ? 'block' : 'none';
    });

    // 新增快捷範本按鈕
    document.getElementById('btn-add-quickadd').addEventListener('click', () => {
        qaEditingId = null;
        document.getElementById('qa-modal-title').textContent = '新增快捷範本';
        resetQaForm();
        document.getElementById('qa-modal').classList.add('active');
    });

    function resetQaForm() {
        document.getElementById('qa-name').value = '';
        document.getElementById('qa-template-type').value = 'record';
        document.getElementById('qa-type').value = '支出';
        document.getElementById('qa-amount').value = '';
        document.getElementById('qa-item').value = '';
        document.getElementById('qa-note').value = '';
        document.getElementById('qa-transfer-amount').value = '';
        document.getElementById('qa-transfer-note').value = '';
        document.getElementById('qa-record-fields').style.display = 'block';
        document.getElementById('qa-transfer-fields').style.display = 'none';
        if (accountsCache.length > 0) {
            document.getElementById('qa-account').value = accountsCache[0].id;
            document.getElementById('qa-from-account').value = accountsCache[0].id;
            if (accountsCache.length > 1) {
                document.getElementById('qa-to-account').value = accountsCache[1].id;
            }
        }
        if (categoriesCache.length > 0) {
            document.getElementById('qa-category').value = categoriesCache[0].id;
        }
    }

    async function editQuickAdd(id) {
        try {
            const templates = await API.getQuickAddTemplates();
            const t = templates.find(x => x.id === id);
            if (!t) return;

            qaEditingId = id;
            document.getElementById('qa-modal-title').textContent = '編輯快捷範本';
            document.getElementById('qa-name').value = t.name;
            document.getElementById('qa-template-type').value = t.template_type;

            const isTransfer = t.template_type === 'transfer';
            document.getElementById('qa-record-fields').style.display = isTransfer ? 'none' : 'block';
            document.getElementById('qa-transfer-fields').style.display = isTransfer ? 'block' : 'none';

            if (isTransfer) {
                if (t.account_id) document.getElementById('qa-from-account').value = t.account_id;
                if (t.to_account_id) document.getElementById('qa-to-account').value = t.to_account_id;
                document.getElementById('qa-transfer-amount').value = t.amount || '';
                document.getElementById('qa-transfer-note').value = t.note || '';
            } else {
                if (t.account_id) document.getElementById('qa-account').value = t.account_id;
                document.getElementById('qa-type').value = t.type || '支出';
                document.getElementById('qa-amount').value = t.amount || '';
                document.getElementById('qa-item').value = t.item || '';
                if (t.category_id) document.getElementById('qa-category').value = t.category_id;
                document.getElementById('qa-note').value = t.note || '';
            }

            document.getElementById('qa-modal').classList.add('active');
        } catch (e) {
            showToast('載入範本失敗');
        }
    }

    async function deleteQuickAdd(id, name) {
        if (!confirm(`確定要刪除快捷範本「${name}」嗎？`)) return;
        try {
            await API.deleteQuickAddTemplate(id);
            showToast('刪除成功');
            loadQuickAddTemplates();
        } catch (e) {
            showToast(e.message || '刪除失敗');
        }
    }

    // 快捷範本彈窗取消
    document.getElementById('qa-modal-cancel').addEventListener('click', () => {
        document.getElementById('qa-modal').classList.remove('active');
    });

    // 快捷範本彈窗確定
    document.getElementById('qa-modal-confirm').addEventListener('click', async () => {
        const name = document.getElementById('qa-name').value.trim();
        if (!name) {
            showToast('請輸入範本名稱');
            return;
        }

        const templateType = document.getElementById('qa-template-type').value;
        let data = { name, template_type: templateType };

        if (templateType === 'transfer') {
            data.account_id = parseInt(document.getElementById('qa-from-account').value);
            data.to_account_id = parseInt(document.getElementById('qa-to-account').value);
            data.amount = parseFloat(document.getElementById('qa-transfer-amount').value) || 0;
            data.note = document.getElementById('qa-transfer-note').value;
        } else {
            data.account_id = parseInt(document.getElementById('qa-account').value);
            data.type = document.getElementById('qa-type').value;
            data.amount = parseFloat(document.getElementById('qa-amount').value) || 0;
            data.item = document.getElementById('qa-item').value;
            data.category_id = parseInt(document.getElementById('qa-category').value);
            data.note = document.getElementById('qa-note').value;
        }

        try {
            if (qaEditingId) {
                await API.updateQuickAddTemplate(qaEditingId, data);
                showToast('更新成功');
            } else {
                await API.createQuickAddTemplate(data);
                showToast('新增成功');
            }
            document.getElementById('qa-modal').classList.remove('active');
            loadQuickAddTemplates();
        } catch (e) {
            showToast(e.message || '操作失敗');
        }
    });

    // ========== 分類管理 ==========

    async function loadCategories() {
        try {
            const categories = await API.getCategories();
            const listEl = document.getElementById('category-list');

            if (!categories || categories.length === 0) {
                listEl.innerHTML = '<div class="empty-message">尚無分類</div>';
                return;
            }

            listEl.innerHTML = categories.map(c => `
                <div class="setting-item" data-id="${c.id}">
                    <span class="setting-name">${escapeHtml(c.name)}</span>
                    <div class="setting-actions">
                        <button onclick="editCategory(${c.id}, '${escapeAttr(c.name)}')">編輯</button>
                        <button class="btn-del" onclick="deleteCategory(${c.id}, '${escapeAttr(c.name)}')">刪除</button>
                    </div>
                </div>
            `).join('');
        } catch (e) {
            showToast('載入分類失敗');
        }
    }

    function editCategory(id, name) {
        editingId = id;
        document.getElementById('modal-title').textContent = '編輯分類';
        document.getElementById('modal-name').value = name;
        document.getElementById('modal').classList.add('active');
    }

    async function deleteCategory(id, name) {
        if (!confirm(`確定要刪除分類「${name}」嗎？`)) return;
        try {
            await API.deleteCategory(id);
            showToast('刪除成功');
            loadCategories();
        } catch (e) {
            showToast(e.message || '刪除失敗');
        }
    }

    document.getElementById('btn-add-category').addEventListener('click', () => {
        editingId = null;
        document.getElementById('modal-title').textContent = '新增分類';
        document.getElementById('modal-name').value = '';
        document.getElementById('modal').classList.add('active');
    });

    document.getElementById('modal-cancel').addEventListener('click', () => {
        document.getElementById('modal').classList.remove('active');
    });

    document.getElementById('modal-confirm').addEventListener('click', async () => {
        const name = document.getElementById('modal-name').value.trim();
        if (!name) {
            showToast('請輸入分類名稱');
            return;
        }

        try {
            if (editingId) {
                await API.updateCategory(editingId, { name });
                showToast('更新成功');
            } else {
                await API.createCategory({ name });
                showToast('新增成功');
            }
            document.getElementById('modal').classList.remove('active');
            loadCategories();
        } catch (e) {
            showToast(e.message || '操作失敗');
        }
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function escapeAttr(text) {
        return text.replace(/'/g, "\\'").replace(/"/g, '\\"');
    }

    // 初始化
    loadFormOptions();
    loadQuickAddTemplates();
    loadCategories();
</script>

<?php include __DIR__ . '/components/footer.php'; ?>
