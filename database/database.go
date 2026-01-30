package database

import (
	"context"
	"fmt"
	"os"

	"github.com/jackc/pgx/v5"
)

// DB é a variável global que segurará a conexão
var DB *pgx.Conn

func Connect() {
	// Substitua pela sua string do Supabase
	connString := "postgresql://postgres:***REMOVED***@db.oztrondqqsnuhuzmernv.supabase.co:5432/postgres"

	var err error
	// := aqui criaria uma variável local, então usamos apenas = para a global
	DB, err = pgx.Connect(context.Background(), connString)

	if err != nil {
		fmt.Fprintf(os.Stderr, "Erro ao conectar no Supabase: %v\n", err)
		os.Exit(1)
	}

	fmt.Println("Conexão com Supabase (Postgres) estabelecida com sucesso!")
}
