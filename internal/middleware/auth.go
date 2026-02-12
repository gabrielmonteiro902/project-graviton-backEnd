package middleware

import (
	"os"

	"github.com/gofiber/fiber/v2"
	"github.com/golang-jwt/jwt/v5"
)

func Protected() fiber.Handler {
	return func(c *fiber.Ctx) error {
		cookie := c.Cookies("jwt")

		if cookie == "" {
			return c.Status(401).JSON(fiber.Map{"error": "Não autorizado"})
		}

		token, err := jwt.Parse(cookie, func(token *jwt.Token) (interface{}, error) {
			return []byte(os.Getenv("JWT_SECRET")), nil
		})

		if err != nil || !token.Valid {
			return c.Status(401).JSON(fiber.Map{"error": "Token inválido"})
		}

		claims := token.Claims.(jwt.MapClaims)
		c.Locals("admin_id", claims["admin_id"])

		return c.Next()
	}
}
