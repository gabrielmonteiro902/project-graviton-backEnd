package models

import "time"

type Admin struct {
	ID        string    `json:"id"`
	Name      string    `json:"name_admin"`
	Email     string    `json:"email_admin"`
	Password  string    `json:"password_admin"`
	CreatedAt time.Time `json:"created_at"`
}
