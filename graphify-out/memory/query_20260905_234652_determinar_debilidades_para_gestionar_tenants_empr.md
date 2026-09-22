---
type: "query"
date: "2026-09-05T23:46:52.897124+00:00"
question: "Determinar debilidades para gestionar tenants empresariales y comercializar productos de software como firma electronica, ISO, privacidad y ensenanza"
contributor: "graphify"
outcome: "useful"
source_nodes: ["Tenant", "TenantEntitlements", "TenantPlanSubscriber", "Subscription", "LimitType", "TenantDatabaseSeeder"]
---

# Q: Determinar debilidades para gestionar tenants empresariales y comercializar productos de software como firma electronica, ISO, privacidad y ensenanza

## Answer

Expanded from original query via vocab: [tenant, tenants, subscription, plan, module, feature, limit, permission, role, domain, isolated, billing]. Hallazgos: credencial demo comun test@example.com/password en cada tenant; entitlements no validan estado ni vigencia de suscripcion; limites solo se muestran y no se consumen; catalogo de precios no esta conectado a pagos, facturas ni snapshot contractual; add-ons carecen de flujo comercial; cambios programados no estan registrados en scheduler; cambios de plan pueden dejar cache stale si no cambia ningun modulo; aprovisionamiento sincronico carece de estado/reintento; administracion solo crea y consulta tenants; Tenant no modela identidad legal de empresa; applyChange no usa transaccion/bloqueo y subscription_modules no tiene unicidad; una suscripcion unica por tenant limita contratos independientes por producto. Base positiva: bases aisladas, catalogo modular, ACL dual y cambios de plan.

## Outcome

- Signal: useful

## Source Nodes

- Tenant
- TenantEntitlements
- TenantPlanSubscriber
- Subscription
- LimitType
- TenantDatabaseSeeder