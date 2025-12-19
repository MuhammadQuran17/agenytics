# Dev Documentation of Ai agent starter kit

## IMPORTANT

- If you will move queue processor from db to redis, make sure that UserJobLimiter is also fixed 

## Frontend

- in resources/js/store we have used Pinia Store Management
- We use ready to use Laravel's Vue js, Starter kit, that uses `shadcn-ui`. Global reusable components of shadcn-ui is located in `js/components`.
- in `js/components_project` we have project components. It is like Services. First if we need new page, we create new file in js/pages, then we create in `componets_project` new components to isolate some logic to aquire SPR (single principle responsibility)
- If you want to understand/modify huge components like ChatBot.vue, it's recommended first to fold entire file and you will see hint comments like [START] and [END]. So open only neccessary parts.

## Backend

- We are using Controllers and Services
- in AiCHat we have used Strategy Pattern for Responses. Registered in ServiceProvider. In Factories we have strategy resolving logic
- We have integration with N8N service in `app/Services/N8N/N8nAiAgent.php`. 
- in .env if you make true `IS_FAKE_RESPONSES_ENABLED` parameter, then you will get mock/test response from N8N, and test entire chat functionality.
- For Tests we have used pest framework.
- We use Mailpit for email local testing just call localhost:8025 and UI will be available.

## DB

1. chat_histories save user input in `user_input` column and all unneccessary columns are empty. It save `ai response` in all columns except `user_input`. 

## Feedbacks

1. feedback is the same FeatureRequest. The same entity

### Coolify deployment

1. Select N8n+PostgreSQL+Worker in Coolify Services. You can change TZ in environment varaibles. Default currently is Europe/Berlin. Press Deploy 
2. Select PgSql in Coolify Services.
3. Go to Servers, Select your Server, go to Destinations in sidebar, and click Scan For Destinations. You should see that new network is available, click to add. It is the network of N8N + Worker + Postgres + Redis
3. https://coolify.io/docs/knowledge-base/docker/compose#connect-to-predefined-networks so go to https://coolify.io/docs/knowledge-base/docker/compose#connect-to-predefined-networks
3. Go to the Terminal of your Server in Coolify. Because our containers are running we should add container to network with : 
`docker network connect my-custom-network my-running-container` 
4. Check the permission of Laravel folder. If it is root then chown to www-data
5. Enable Healthcheck in Laravel app. Just change Path to /up. Other thing are good by default. Save and Enable Healthcheck. If you will get problems in future the debugging steps are clear in Coolify docs: https://coolify.io/docs/troubleshoot/applications/no-available-server . Healthcheck docs: https://coolify.io/docs/knowledge-base/health-checks
6. In post-deployment commands in Laravel app paste this:
`php artisan optimize:clear && php artisan optimize && chown -R www-data:www-data *` But Please ehceck by yourself always is folders was changed to www-data, because sometimes you should do it manually. Check what permission is for laravel.log