terraform {
  required_version = ">= 1.0"

  required_providers {
    kubernetes = {
      source  = "hashicorp/kubernetes"
      version = "~> 2.0"
    }
  }
}

provider "kubernetes" {
  config_path = "~/.kube/config"
}

resource "kubernetes_namespace" "staging" {
  metadata {
    name = "library-staging"

    labels = {
      environment = "staging"
      managed-by  = "terraform"
      app         = "library-app"
    }
  }
}

resource "kubernetes_namespace" "production" {
  metadata {
    name = "library-production"

    labels = {
      environment = "production"
      managed-by  = "terraform"
      app         = "library-app"
    }
  }
}

resource "kubernetes_resource_quota" "staging_quota" {
  metadata {
    name      = "staging-quota"
    namespace = kubernetes_namespace.staging.metadata[0].name
  }

  spec {
    hard = {
      "requests.cpu"    = "1"
      "requests.memory" = "1Gi"
      "limits.cpu"      = "2"
      "limits.memory"   = "2Gi"
      pods              = "10"
    }
  }
}

resource "kubernetes_resource_quota" "production_quota" {
  metadata {
    name      = "production-quota"
    namespace = kubernetes_namespace.production.metadata[0].name
  }

  spec {
    hard = {
      "requests.cpu"    = "2"
      "requests.memory" = "2Gi"
      "limits.cpu"      = "4"
      "limits.memory"   = "4Gi"
      pods              = "20"
    }
  }
}
