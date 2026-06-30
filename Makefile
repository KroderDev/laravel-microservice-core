PHP = docker compose run --rm php
COMPOSER = docker compose run --rm composer

.DEFAULT_GOAL := help

install: ## Install dependencies
	$(COMPOSER) install --no-interaction --prefer-dist

update: ## Update dependencies
	$(COMPOSER) update

test: ## Run test suite
	$(COMPOSER) test

lint: ## Check code style (PSR-12)
	$(COMPOSER) run print-test

lint-fix: ## Fix code style automatically
	$(COMPOSER) run print

shell: ## Open a PHP shell in the container
	$(PHP) -a

help: ## Show this help
	@echo "Usage: make <target>"
	@echo ""
	@echo "Available targets:"
	@echo "  install     Install dependencies"
	@echo "  update      Update dependencies"
	@echo "  test        Run test suite"
	@echo "  lint        Check code style (PSR-12)"
	@echo "  lint-fix    Fix code style automatically"
	@echo "  shell       Open a PHP shell in the container"
	@echo "  help        Show this help"

.PHONY: install update test lint lint-fix shell help
