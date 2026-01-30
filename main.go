package main

import (
	"log"

	"github.com/gabrielmonteiro/graviton-api/database"
	"github.com/gabrielmonteiro/graviton-api/internal/handlers"

	"github.com/gofiber/fiber/v2"
)

func main() {
	database.Connect()
	app := fiber.New()

	//Admin Group
	v1 := app.Group("/v1")
	adminGroup := v1.Group("/admins")
	adminGroup.Get("/", handlers.GetAdmins)
	adminGroup.Post("/", handlers.CreateAdmin)
	adminGroup.Put("/:id", handlers.UpdateAdmin)
	adminGroup.Delete("/:id", handlers.DeleteAdmin)
	log.Fatal(app.Listen(":3000"))

	app.Get("/health", func(c *fiber.Ctx) error {
		return c.JSON(fiber.Map{"status": "ok", "message": "Graviton API Online"})
	})

	log.Fatal(app.Listen(":3000"))
}
