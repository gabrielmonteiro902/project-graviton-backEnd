package handlers

import (
	"context"

	"github.com/gabrielmonteiro/graviton-api/database"
	"github.com/gabrielmonteiro/graviton-api/internal/models"
	"github.com/gofiber/fiber/v2"
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
