package models

// QuickAddTemplate 快捷新增範本
type QuickAddTemplate struct {
	ID           int     `json:"id"`
	Name         string  `json:"name"`
	TemplateType string  `json:"template_type"` // "record" 或 "transfer"
	AccountID    *int    `json:"account_id"`
	Type         string  `json:"type"`
	Amount       float64 `json:"amount"`
	Item         string  `json:"item"`
	CategoryID   *int    `json:"category_id"`
	Note         string  `json:"note"`
	ToAccountID  *int    `json:"to_account_id"`
	SortOrder    int     `json:"sort_order"`
}

// QuickAddTemplateWithNames 帶有名稱的範本（API 回應用）
type QuickAddTemplateWithNames struct {
	ID            int     `json:"id"`
	Name          string  `json:"name"`
	TemplateType  string  `json:"template_type"`
	AccountID     *int    `json:"account_id"`
	AccountName   string  `json:"account_name"`
	Type          string  `json:"type"`
	Amount        float64 `json:"amount"`
	Item          string  `json:"item"`
	CategoryID    *int    `json:"category_id"`
	CategoryName  string  `json:"category_name"`
	Note          string  `json:"note"`
	ToAccountID   *int    `json:"to_account_id"`
	ToAccountName string  `json:"to_account_name"`
	SortOrder     int     `json:"sort_order"`
}

// QuickAddTemplateInput 新增/更新範本的輸入
type QuickAddTemplateInput struct {
	Name         string  `json:"name" binding:"required"`
	TemplateType string  `json:"template_type" binding:"required"`
	AccountID    *int    `json:"account_id"`
	Type         string  `json:"type"`
	Amount       float64 `json:"amount"`
	Item         string  `json:"item"`
	CategoryID   *int    `json:"category_id"`
	Note         string  `json:"note"`
	ToAccountID  *int    `json:"to_account_id"`
}
