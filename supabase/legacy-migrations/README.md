# Legacy SQL Reference

Files in this directory are historical development scripts. They are not part
of the deployable Supabase migration chain and must not be executed against a
production project without a manual review.

The canonical migration chain is the timestamped SQL in
`supabase/migrations`. Use `supabase migration list --linked` and
`supabase db push --linked --dry-run` before every production database change.

Some legacy files contain placeholder credentials. Real credentials belong in
Supabase Vault, Edge Function secrets, or local environment variables and must
never be committed to Git.
