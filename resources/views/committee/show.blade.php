@extends('website.layouts.default-nobg')

@section('page-title')
    {{ $committee->name }}
@endsection

@section('content')

    <div class="row">

        <div class="col-md-{{ (Auth::check() && Auth::user()->member ? '7' : '6 col-md-offset-3') }}">

            @if($committee->image)
                <img src="{{ $committee->image->generateImagePath(800,300) }}"
                     style="width: 100%; margin-bottom: 30px; box-shadow: 0 0 20px -7px #000;">
            @endif

            <div class="panel panel-default container-panel">

                <div class="panel-body">

                    {!! Markdown::convertToHtml($committee->description) !!}

                    <hr>

                    If you want, you can e-mail them at
                    <a href="mailto:{{ $committee->slug . "@" . config('proto.emaildomain') }}">
                        {{ $committee->slug . "@" . config('proto.emaildomain') }}
                    </a>.

                </div>

                @if(Auth::check() && ($committee->allow_anonymous_email || Auth::user()->can('board')))

                    <div class="panel-footer clearfix">

                        @if(Auth::check() && Auth::user()->can('board'))

                            <a href="{{ route("committee::edit", ["id" => $committee->id]) }}"
                               class="btn btn-default pull-right">
                                Edit
                            </a>

                        @endif

                        @if($committee->allow_anonymous_email)

                            <a href="{{ route("committee::anonymousmail", ["id" => $committee->id]) }}"
                               class="btn btn-info">
                                Send this committee an anonymous e-mail
                            </a>

                        @endif

                    </div>

                @endif

            </div>

            @if(Auth::check() && Auth::user()->member)

                @if(count($committee->upcomingEvents()) > 0)

                    <div class="panel panel-default calendar">

                        <div class="panel-heading">
                            Upcoming Events
                        </div>

                        <div class="panel-body">

                            <div class="row">

                                @foreach($committee->upcomingEvents() as $key => $event)

                                    <div class="col-md-6">

                                        <a class="activity"
                                           href="{{ route('event::show', ['id' => $event->id]) }}">
                                            <div class="activity {{ ($key % 2 == 1 ? 'odd' : '') }}" {!! ($event->secret ? 'style="opacity: 0.3;"' : '') !!}>
                                                <p><strong>{{ $event->title }}</strong></p>
                                                <p><i class="fa fa-map-marker"
                                                      aria-hidden="true"></i> {{ $event->location }}
                                                </p>
                                                <p>
                                                    <i class="fa fa-clock-o" aria-hidden="true"></i>
                                                    {{ date('l j F', $event->start) }}, {{ date('H:i', $event->start) }}
                                                    -
                                                    @if (($event->end - $event->start) < 3600 * 24)
                                                        {{ date('H:i', $event->end) }}
                                                    @else
                                                        {{ date('j M, H:i', $event->end) }}
                                                    @endif
                                                </p>
                                            </div>
                                        </a>

                                    </div>

                                @endforeach

                            </div>

                        </div>

                    </div>

                @endif

                @if(count($committee->pastEvents()) > 0)

                    <div class="panel panel-default calendar">

                        <div class="panel-heading">
                            Past Events
                        </div>

                        <div class="panel-body">

                            <div class="row">

                                @foreach($committee->pastEvents() as $key => $event)

                                    <div class="col-md-6">

                                        <a class="activity"
                                           href="{{ route('event::show', ['id' => $event->id]) }}">
                                            <div class="activity {{ ($key % 2 == 1 ? 'odd' : '') }}" {!! ($event->secret ? 'style="opacity: 0.3;"' : '') !!}>
                                                <p><strong>{{ $event->title }}</strong></p>
                                                <p><i class="fa fa-map-marker"
                                                      aria-hidden="true"></i> {{ $event->location }}
                                                </p>
                                                <p>
                                                    <i class="fa fa-clock-o" aria-hidden="true"></i>
                                                    {{ date('l j F', $event->start) }}, {{ date('H:i', $event->start) }}
                                                    -
                                                    @if (($event->end - $event->start) < 3600 * 24)
                                                        {{ date('H:i', $event->end) }}
                                                    @else
                                                        {{ date('j M, H:i', $event->end) }}
                                                    @endif
                                                </p>
                                            </div>
                                        </a>

                                    </div>

                                @endforeach

                            </div>

                        </div>
                    </div>

                @endif

                @if(count($committee->helpedEvents()) > 0)

                    <div class="panel panel-default calendar">

                        <div class="panel-heading">
                            Helped Events
                        </div>

                        <div class="panel-body">

                            <div class="row">

                                @foreach($committee->helpedEvents() as $key => $event)

                                    @if($key % 2 == 0 && $key > 1)
                                        <? echo '</div><div class="row">'?>
                                    @endif

                                    <div class="col-md-6">

                                        <a class="activity"
                                           href="{{ route('event::show', ['id' => $event['id']]) }}">
                                            <div class="activity" {!! ($event['secret'] ? 'style="opacity: 0.3;"' : '') !!}>
                                                <p><strong>{{ $event['title'] }}</strong></p>
                                                <p><i class="fa fa-map-marker"
                                                      aria-hidden="true"></i> {{ $event['location'] }}
                                                </p>
                                                <p>
                                                    <i class="fa fa-clock-o" aria-hidden="true"></i>
                                                    {{ date('l j F Y', $event['start']) }}
                                                    , {{ date('H:i', $event['start']) }}
                                                    -
                                                    @if (($event['end'] - $event['start']) < 3600 * 24)
                                                        {{ date('H:i', $event['end']) }}
                                                    @else
                                                        {{ date('j M, H:i', $event['end']) }}
                                                    @endif
                                                </p>
                                            </div>
                                        </a>

                                    </div>

                                @endforeach

                            </div>

                        </div>

                    </div>

                @endif

            @endif

        </div>

        @if(Auth::check() && Auth::user()->member)

            <div class="col-md-5">

                @if(!$committee->public)
                    <div class="btn-group btn-group-justified">
                        <a class="btn btn-info">
                            This committee is hidden!
                        </a>
                    </div>
                    <br>
                @endif

                @include('committee.members-list')

            </div>

        @endif

    </div>

@endsection

@section('stylesheet')

    @parent

    <style type="text/css">

        .committee-seperator {
            margin: 10px 0;
        }

    </style>

@endsection