output "staging_namespace" {
  value       = kubernetes_namespace.staging.metadata[0].name
  description = "Staging namespace name"
}

output "production_namespace" {
  value       = kubernetes_namespace.production.metadata[0].name
  description = "Production namespace name"
}
