# Roadmap

Planned capabilities for future releases. Priorities may shift based on community feedback.

## v0.7 — Resilience

- **Circuit breaker**: Per-service configurable failure thresholds with half-open state recovery.
- **Bulkhead isolation**: Separate HTTP connection pools per service to prevent cascading failures.
- **Retry with exponential backoff + jitter**: Configurable retry strategies beyond the current fixed retry.

## v0.8 — Service Discovery

- **DNS-based resolution**: Resolve service URLs via DNS SRV records.
- **Consul integration**: Dynamic service endpoint discovery from Consul catalog.
- **Kubernetes service support**: Resolve services via K8s internal DNS.

## v0.9 — Observability

- **OpenTelemetry tracing**: W3C Trace Context (`traceparent`/`tracestate`) header propagation.
- **Span creation**: Automatic spans for inter-service HTTP calls with configurable sampling.
- **Metrics export**: Prometheus-compatible metrics for request counts, latency, and error rates.

## v1.0 — Event-Driven Communication

- **Message broker abstraction**: Unified interface for RabbitMQ, SQS, and Kafka.
- **Async event publishing**: Publish events with correlation context propagation across async boundaries.
- **Inbox/outbox pattern**: Transactional outbox helpers for reliable event delivery.

## v1.1 — Distributed Transactions

- **Saga orchestration primitives**: Define saga steps with compensation actions.
- **Saga state persistence**: Pluggable storage backends for saga state.
- **Compensation action registration**: Auto-invoke compensating actions on step failure.

## Later

- **API version negotiation middleware**: Header-based and URL-prefix-based version routing between services.
- **Rate limit awareness**: Parse `Retry-After` headers and back off automatically.
- **Dead letter handling**: Configurable DLQ routing for failed events.
