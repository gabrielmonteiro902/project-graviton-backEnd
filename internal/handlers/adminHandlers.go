package handlers

import (
	"context"
	"os"
	"time"

	"github.com/gabrielmonteiro/graviton-api/database"
	"github.com/gabrielmonteiro/graviton-api/internal/models"
	"github.com/gofiber/fiber/v2"
	"github.com/golang-jwt/jwt/v5"

	"golang.org/x/crypto/bcrypt"
)

func GetAdmins(c *fiber.Ctx) error {
	rows, err := database.DB.Query(context.Background(), "SELECT id, name_admin, email_admin FROM admins")
	if err != nil {
		return c.Status(500).JSON(fiber.Map{"error": "Falha ao consultar o banco de dados!"})
	}

	defer rows.Close()

	admins := []models.Admin{}
	for rows.Next() {
		var a models.Admin
		if err := rows.Scan(&a.ID, &a.Name, &a.Email); err != nil {
			continue
		}
		admins = append(admins, a)
	}
	return c.JSON(admins)

}

func CreateAdmin(c *fiber.Ctx) error {
	admin := new(models.Admin)

	if err := c.BodyParser(admin); err != nil {
		return c.Status(400).JSON(fiber.Map{"error": "Formato de dados inválido"})
	}

	hashedPassword, err := bcrypt.GenerateFromPassword([]byte(admin.Password), 10)
	if err != nil {
		return c.Status(500).JSON(fiber.Map{"error": "Erro ao processar senha"})
	}

	admin.Password = string(hashedPassword)

	query := "INSERT INTO admins (name_admin, email_admin, password_admin) VALUES ($1, $2, $3) RETURNING id"

	err = database.DB.QueryRow(context.Background(), query, admin.Name, admin.Email, admin.Password).Scan(&admin.ID)
	if err != nil {
		return c.Status(500).JSON(fiber.Map{"error": "Erro ao salvar no Supabase"})
	}

	admin.Password = ""
	return c.Status(201).JSON(admin)
}

func UpdateAdmin(c *fiber.Ctx) error {
	id := c.Params("id")
	admin := new(models.Admin)

	if err := c.BodyParser(admin); err != nil {
		return c.Status(400).JSON(fiber.Map{"error": "JSON inválido"})
	}

	if admin.Password != "" {
		hashedPassword, err := bcrypt.GenerateFromPassword([]byte(admin.Password), 10)
		if err != nil {
			return c.Status(200).JSON(fiber.Map{"data": "Senha atualizada com sucesso"})
		}
		admin.Password = string(hashedPassword)
	}

	query := "UPDATE admins SET name_admin = $1, email_admin = $2, password_admin = $3 WHERE id = $4 "
	res, err := database.DB.Exec(context.Background(), query, admin.Name, admin.Email, admin.Password, id)
	if err != nil {
		return c.Status(500).JSON(fiber.Map{"error": "Erro ao atualizar"})
	}

	if res.RowsAffected() == 0 {
		return c.Status(404).JSON(fiber.Map{"error": "Admin não encontrado "})
	}

	return c.JSON(fiber.Map{"message": "Atualizado com sucesso"})
}

func Login(c *fiber.Ctx) error {
	type LoginRequest struct {
		Email    string `json:"email_admin"`
		Password string `json:"password_admin"`
	}

	var request LoginRequest
	if err := c.BodyParser(&request); err != nil {
		return c.Status(400).JSON(fiber.Map{"error": "JSON inválido"})
	}

	var admin models.Admin
	query := "SELECT id, password_admin FROM admins WHERE email_admin = $1"
	err := database.DB.QueryRow(context.Background(), query, request.Email).Scan(&admin.ID, &admin.Password)

	if err != nil {
		return c.Status(400).JSON(fiber.Map{"error": "Email ou senha inválidos"})
	}

	if err := bcrypt.CompareHashAndPassword([]byte(admin.Password), []byte(request.Password)); err != nil {
		return c.Status(400).JSON(fiber.Map{"error": "Email ou senha inválidos"})
	}

	claims := jwt.MapClaims{
		"admin_id": admin.ID,
		"exp":      time.Now().Add(time.Hour * 24).Unix(),
	}

	token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
	t, err := token.SignedString([]byte(os.Getenv("JWT_SECRET")))
	if err != nil {
		return c.SendStatus(fiber.StatusInternalServerError)
	}

	cookie := fiber.Cookie{
		Name:     "jwt",
		Value:    t,
		Expires:  time.Now().Add(time.Hour * 72),
		HTTPOnly: true,
		Secure:   true,
		SameSite: "Lax",
	}
	c.Cookie(&cookie)

	return c.JSON(fiber.Map{"message": "Login realizado com sucesso"})
}

func GetMyProfile(c *fiber.Ctx) error {

	adminID := c.Locals("admin_id")

	if adminID == nil {
		return c.Status(401).JSON(fiber.Map{"error": "ID não encontrado no token"})
	}

	var admin models.Admin
	query := "SELECT id, name_admin, email_admin FROM admins WHERE id = $1"

	err := database.DB.QueryRow(context.Background(), query, adminID).Scan(
		&admin.ID,
		&admin.Name,
		&admin.Email,
	)

	if err != nil {
		return c.Status(404).JSON(fiber.Map{"error": "Usuário não existe no banco de dados"})
	}

	return c.JSON(admin)
}

func GetAdminByID(c *fiber.Ctx) error {
	id := c.Params("id")
	var admin models.Admin

	query := "SELECT id, admin_name, email_admin FROM admins WHERE id = $1ß"

	err := database.DB.QueryRow(context.Background(), query, id).Scan(&admin.ID, &admin.Name, &admin.Email)
	if err != nil {
		return c.Status(400).JSON(fiber.Map{"error": "Administrador não encontrado"})
	}

	return c.JSON(admin)
}

func DeleteAdmin(c *fiber.Ctx) error {
	id := c.Params("id")

	commandTag, err := database.DB.Exec(context.Background(), "DELETE FROM admins WHERE id = $1", id)
	if err != nil {
		return c.Status(404).JSON(fiber.Map{"error": "Não foi possivel deletar esse administrador"})
	}

	if commandTag.RowsAffected() == 0 {
		return c.Status(404).JSON(fiber.Map{"error": "Administrador não encontrado"})
	}

	return c.Status(200).JSON(fiber.Map{"message": "Administrador removida com sucesso"})
}
