<?php
include 'login.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 <title>y!jeopardy</title>
 <link type="text/css" href="s/main.css" rel="stylesheet" media="screen, projection" />
 <script type="text/javascript" src="a/prototype.js"></script>
 <script type="text/javascript" src="a/scriptaculous/scriptaculous.js"></script>
 <script type="text/javascript" src="a/livepipe/livepipe.js"></script>
 <script type="text/javascript" src="a/livepipe/scrollbar.js"></script>
 <script type="text/javascript" src="a/jst/util.js"></script>
 <script type="text/javascript" src="a/jst/jsevalcontext.js"></script>
 <script type="text/javascript" src="a/jst/jstemplate.js"></script>
 <script type="text/javascript">
/* <![CDATA[ */

var questioning,
    questioningInstance,
    questioningPE,
    choosing,
    choosingCategory,
    choosingPE,
    time,
    timer,
    stopTimer,
    updateTimer,
    timerPE,
    timerDots,
    blinking,
    blinkingPE,
    lastShowing,

    STATE = 'INIT',
    PLAYER = '',
    SCROLL = null,
    TIME = null,
    questioningStep = 0;

Event.observe(document, 'dom:loaded', function()
{
    // functions
    questioning = function()
    {
        this.className = 'question step' + questioningStep;
        if (++questioningStep > 11) questioningStep = 0;
    }

    choosing = function()
    {
        if (!choosingCategory)
        {
            choosingCategory = $$('#board .category').last();
        }

        choosingCategory.removeClassName('selecting');

        if (!(choosingCategory = choosingCategory.next()))
        {
            choosingCategory = $$('#board .category').first();
        }

        choosingCategory.addClassName('selecting');
    }

    blinking = function(rate)
    {
        new Effect.Opacity($('timer'), {from: 1.0, to: 0.0, transition: Effect.Transitions.linear, duration: rate});
        new Effect.Opacity($('timer'), {from: 0.0, to: 1.0, transition: Effect.Transitions.linear, duration: rate, delay: rate});
    }

    timer = function()
    {
        jstProcess(new JsEvalContext({time: TIME, dots: timerDots}), $('timer'));

        switch (TIME)
        {
            case 20:
            case 10:
            case 4:
            case 3:
            case 2:
            case 1:
            case 0:
                blinking(0.15);
                break;
        }

        if (!TIME && timerPE)
        {
            blinking.delay(0.2, 0.1);
            blinking.delay(0.4, 0.1);
            blinking.delay(0.6, 0.1);
            $('timer').fade({duration: 0.25, delay: 0.8});
            timerPE.stop();
        }
        if (TIME) TIME--;
    }
    timer();

    stopTimer = function()
    {
        if (timerPE) timerPE.stop();
        $('timer').fade(
        {
            duration: 0.3,
            afterFinish: function()
            {
                TIME = null;
                timer();
            }
        });
    }

    updateTimer = function(left)
    {
        TIME = left;
        timerDots = $R(1, TIME).toArray();

        $('timer').hide().appear(0.3);

        timerPE = new PeriodicalExecuter(timer, 1);
        timer();
    }

    updateStatus = function(message)
    {
        $('status').update(message);
    }

    // {{{ Live updating
    // players list
    YJ.Tools.liveUpdate('get_players', 0.5, function(response)
    {
        var active;

        if (response.responseJSON.status == 'ok')
        {
            // update players
            jstProcess(new JsEvalContext(response.responseJSON.data), $('players'));

            if ((active = $$('#players li.active').first()) && (PLAYER != active))
            {
                if (!SCROLL)
                {
                    SCROLL = new Control.ScrollBar('players', 'dummy');
                }

                PLAYER = active;
                SCROLL.recalculateLayout();
                SCROLL.scrollTo(PLAYER, true);
            }
        }
    });

    // get categories
    YJ.Tools.liveUpdate('get_categories', 1, function(response)
    {
        if (response.responseJSON.status == 'ok')
        {
            // update board
            jstProcess(new JsEvalContext(response.responseJSON.data), $('board'));
        }
    });

    // process states
    YJ.Tools.liveUpdate('get_game_state', 0.5, function(response)
    {
        if (response.responseJSON.status == 'ok')
        {
            if (STATE != response.responseJSON.data.game_state)
            {
                var oldState = STATE;
                STATE = response.responseJSON.data.game_state;

                // run teardown if so
                if (oldState in YJ.States && Object.isFunction(YJ.States[oldState].stop)) YJ.States[oldState].stop(STATE);

                // run init if so
                if (STATE in YJ.States && Object.isFunction(YJ.States[STATE].start)) YJ.States[STATE].start(oldState);
            }
        }
    });


    // }}}

});

YJ =
{
    defaults:
    {
        requestBase: 'Request.php'
    }
};

YJ.States =
{
    INIT:
    {
        stop: function()
        {
        }
    },

    PICK_QUESTION:
    {
        start: function()
        {
            $$('#board .qcontainer').invoke('fade', 0.5);
            $$('#board .qcontainer').invoke('remove');
            choosingPE = new PeriodicalExecuter(choosing, 0.8);
            updateStatus('Please pick your question');
        },

        stop: function()
        {
            choosingCategory.removeClassName('selecting');
            if (choosingPE) choosingPE.stop();
            updateStatus();
        }
    },

    DISPLAY_QUESTION:
    {
        start: function()
        {
            YJ.Tools.request('get_question', function(response)
            {
                if (response.responseJSON.status == 'ok')
                {
                    YJ.Tools.showQuestion(response.responseJSON.data);
                }
            });
            updateStatus();
        },

        stop: function()
        {
            updateStatus();
        }
    },

    BUZZ_IN:
    {
        start: function(oldState)
        {
            // if just loaded
            // show question
            if (oldState == 'INIT')
            {
                YJ.Tools.request('get_question', function(response)
                {
                    if (response.responseJSON.status == 'ok')
                    {
                        YJ.Tools.showQuestion(response.responseJSON.data);
                        updateTimer(30);
                    }
                });
            }
            else
            {
                updateTimer(30);
            }

            updateStatus('You can buzz whenever you ready');
        },

        stop: function()
        {
            updateStatus();
            stopTimer();
        }
    },

    ANSWER:
    {
        start: function(oldState)
        {
            // if just loaded
            // show question
            if (oldState == 'INIT')
            {
                YJ.Tools.request('get_question', function(response)
                {
                    if (response.responseJSON.status == 'ok')
                    {
                        YJ.Tools.showQuestion(response.responseJSON.data);
                    }
                });
            }

            updateStatus('Waiting for the answer');
        },

        stop: function()
        {
            updateStatus();
        }
    },

    GAME_OVER:
    {
        start: function(oldState)
        {
            var player;

            $$('#board .qcontainer').invoke('fade', 0.5);
            $$('#board .qcontainer').invoke('remove');

            if ((player = $('players').select('li.player').first())
                && player.down('.score')
                && player.down('.score').innerHTML > 0)
            {
                $('screen').down('div').update($('players').select('li.player').first().down('.name').innerHTML + ' won the game!');
            }
            else
            {
                $('screen').down('div').update('Are you game?');
            }
            $('screen').addClassName('active');
        },
        stop: function()
        {
            $('screen').removeClassName('active');
        }
    },

    PAUSED:
    {
        start: function(oldState)
        {
            $('screen').down('div').update('Paused');
            $('screen').addClassName('active');
        },
        stop: function()
        {
            $('screen').removeClassName('active');
            setTimeout(function(){location.reload(true);}, 500);
        }
    },

    ROUND_OVER:
    {
        start: function(oldState)
        {
            $$('#board .qcontainer').invoke('fade', 0.5);
            $$('#board .qcontainer').invoke('remove');
            $('screen').down('div').update('Round over');
            $('screen').addClassName('active');
        },
        stop: function()
        {
            setTimeout(function(){location.reload(true);}, 500);
        }
    }
};

YJ.Tools =
{
    showQuestion: function(data)
    {
        var question = $('question_'+data.id);

        if (data.question)
        {

            // highlight the question
            if (questioningPE)
            {
                lastShowing.className = 'question';
                questioningPE.stop();
            }

            lastShowing = question.insert({top: new Element('div',
                {
                    'class': 'qcontainer'+((data.dd) ? ' doubles' : '')
                }).insert(new Element('div',
                    {
                        'class': 'data'
                    }).update(data.question)
                )
            });

            new Effect.Move(question.down('.qbox .container'), { x: 0, y: -100, mode: 'relative', duration: 0.5 });

            questioningInstance = questioning.bind(question);
            questioningPE = new PeriodicalExecuter(questioningInstance, 0.12);
            questioningInstance(); // run it right away

            // after effects
            setTimeout(function()
            {
                // remove points
                lastShowing.down('.qbox').remove();
                // start expansion
                var container = lastShowing.down('.qcontainer').setStyle({zIndex: 101});

                var offset = container.viewportOffset().relativeTo($('board').viewportOffset());

                new Effect.Parallel(
                    [
                        new Effect.Morph(container,
                            {
                                style:
                                {
                                    width:  '740px',
                                    height: '578px',
                                    left:   '-'+offset.left+'px',
                                    top:    '-'+offset.top+'px',
                                    padding: '30px 50px'
                                },
                            }
                        ),

                        new Effect.Morph(container.down('.data'),
                            {
                                style:
                                {
                                    width:      '740px',
                                    height:     '538px',
                                    fontSize:   '60px'
                                },
                            }
                        )
                    ],
                    {
                        duration: 0.6,
                        afterFinish: function()
                        {
                            // cleanup hidden stuff
                            if (questioningPE)
                            {
                                lastShowing.className = 'question';
                                questioningPE.stop();
                                questioningPE = null;
                            }
                        }
                    }
                );

            }, 600);
        }
    },

    liveUpdate: function(method, timeout, callback)
    {
        options = Object.deepExtend(
        {
            method: 'get',
            parameters:
            {
                method: method
            },
            timeout: timeout,
            onSuccess: Object.isFunction(callback) ? callback : Prototype.emptyFunction
        }, (Object.isFunction(callback) ? {} : callback) || {});

        return new YJ.LiveUpdater(YJ.defaults.requestBase, options);
    },

    request: function(method, callback)
    {
        options = Object.deepExtend(
        {
            method: 'get',
            parameters:
            {
                method: method
            },
            onSuccess: Object.isFunction(callback) ? callback : Prototype.emptyFunction
        }, (Object.isFunction(callback) ? {} : callback) || {});

        return new Ajax.Request(YJ.defaults.requestBase, options);
    },

    // hoverable mouseover
    hoverableOver: function(e)
    {
        var receiver = e.element();

        receiver.addClassName('hover');

        if (receiver != this)
        {
            receiver.ancestors().find(function(i)
            {
                i.addClassName('hover');

                if (i == this)
                {
                    return true;
                }
            }, this);
        }
    },

    // hoverable mouseoout
    hoverableOut: function(e)
    {
        var receiver = e.element();

        receiver.removeClassName('hover');

        if (receiver != this)
        {
            receiver.ancestors().find(function(i)
            {
                i.removeClassName('hover');
    
                if (i == this)
                {
                    return true;
                }
            }, this);
        }
    }
};

YJ.LiveUpdater = Class.create(Ajax.Base,
{
  initialize: function($super, url, options)
  {
    $super(options);

    this.timeout = (this.options.timeout || 1);

    this.updater = { };

    this.url = url;

    this.start();
  },

  start: function()
  {
    this.options.onComplete = this.updateComplete.bind(this);
    this.onTimerEvent();
  },

  stop: function()
  {
    this.updater.options.onComplete = undefined;
    clearTimeout(this.timer);
    (this.onComplete || Prototype.emptyFunction).apply(this, arguments);
  },

  updateComplete: function(response)
  {
    this.timer = this.onTimerEvent.bind(this).delay(this.timeout);
  },

  onTimerEvent: function()
  {
    this.updater = new Ajax.Request(this.url, this.options);
  }
});

// Borrowed from S2, in case it's not included
if (!Object.deepExtend)
Object.deepExtend = function(destination, source) {
  for (var property in source) {
    if (source[property] && source[property].constructor &&
     source[property].constructor === Object) {
      destination[property] = destination[property] || {};
      arguments.callee(destination[property], source[property]);
    } else {
      destination[property] = source[property];
    }
  }
  return destination;
};


/* ]]> */
 </script>
 <link rel="shortcut icon" href="/favicon.ico">
</head>
<body>

<div id="main">
    <ul id="timer" jsvalues="$time:$this.time;$dots:$this.dots">
     <li jsselect="$this.dots" jsdisplay="$dots" jsvalues=".className:($time > $index) ? 'fill' : 'empty'"></li>
    </ul>
    <ul id="players">
     <li class="empty"></li>
     <li jsselect="$this" jsvalues=".id:'player_'+$this.handle;.className:($this.active)? 'player active' : 'player'" class="player">
      <span jscontent="$this.name" class="name"></span>
      <span jscontent="$this.points" class="score"></span>
      <span jsdisplay="$this.active" class="buzz">&#9728;</span>
     </li>
     <li class="empty"></li>
    </ul>
    <div id="shade"></div>

    <div id="board">
     <div jsselect="$this" jsvalues=".id:$this.id" class="category">
      <div class="head">
       <div jscontent="$this.name" class="content"></div>
      </div>

      <div jsselect="$this.questions" jsvalues=".id:'question_'+$this.id" class="question">
       <div jsdisplay="!$this.played" class="qbox">
        <div class="container">
         <div jscontent="$this.points" class="points"></div>
        </div>
       </div>
      </div>
     </div>
     
    </div>

    <div id="status"></div>
</div>
<div id="screen"><div></div></div>
<div id="dummy"><div></div></div>
</body>
</html>
