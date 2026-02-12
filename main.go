package main

import (
	"log"

	"github.com/gabrielmonteiro/graviton-api/database"
	"github.com/gabrielmonteiro/graviton-api/internal/handlers"
	"github.com/gabrielmonteiro/graviton-api/internal/middleware" // Certifique-se de que o caminho está correto
	"github.com/gofiber/fiber/v2"
	"github.com/gofiber/fiber/v2/middleware/cors"
)

func main() {
	database.Connect()
	app := fiber.New()

	app.Use(cors.New(cors.Config{
		AllowOrigins:     "http://localhost:5173",
		AllowCredentials: true,
		AllowHeaders:     "Origin, Content-Type, Accept",
	}))

	v1 := app.Group("/v1")

	// --- ROTAS PÚBLICAS (Ninguém tem token ainda) ---
	v1.Post("/login", handlers.Login)
	v1.Post("/admins", handlers.CreateAdmin)

	adminProtected := v1.Group("/admins", middleware.Protected())

	v1.Get("/me", middleware.Protected(), handlers.GetMyProfile)

	adminProtected.Get("/", handlers.GetAdmins)
	adminProtected.Get("/:id", handlers.GetAdminByID)
	adminProtected.Put("/:id", handlers.UpdateAdmin)
	adminProtected.Delete("/:id", handlers.DeleteAdmin)

	log.Fatal(app.Listen(":3000"))
}
