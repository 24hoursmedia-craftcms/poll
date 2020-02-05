Example of getting siple results for a poll:


```twig
{% set results = craft.poll.results(poll, {
    with_users: true,
    user_id_only: false,
    limit_users: 1000
}) %}
{# @var \twentyfourhoursmedia\poll\models\PollResults results #}

<div class="alert alert-info">
        <h5 class="card-subtitle mb-3 text-muted">{{ poll.title }}</h5>
        <h6>Results</h6>

        {# show the total poll results, and users info #}
        <p>Total submissions: {{ results.count }}</p>
        <small>
            <em>
                participated user ids: {{ results.userIds | join(', ') }}<br/>
                users:
                {% for user in results.users %}
                    {{ user.username }}{{ not loop.last ? ', ' }}
                {% endfor %}
            </em>
        </small>

        {# show the results by answer #}
        <ul>
            {% for answerResult in results.byAnswer %}
                <li>
                    `{{ answerResult.answer.label }}`: {{ answerResult.count }} votes,
                    {% if answerResult.percent is not null %}
                        {{ answerResult.percent | round }}%
                    {% endif %}
                    <br/>
                    <small>
                        <em>
                            user ids: {{ answerResult.userIds | join(', ') }}<br/>
                            users:
                            {% for user in answerResult.users %}
                                {{ user.username }}{{ not loop.last ? ', ' }}
                            {% endfor %}
                        </em>
                    </small>
                </li>
            {% endfor %}
        </ul>
    </div>

```