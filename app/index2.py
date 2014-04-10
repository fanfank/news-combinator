-*- coding:utf-8 -*-
from flask import Flask, g, request
app = Flask(__name__)
app.debug = True

@app.route('/')
def hello():
    return "Hello, world! - Flask\n"

from bae.core.wsgi import WSGIApplication
application = WSGIApplication(app)

#def app(environ, start_response):
#    status = '200 OK'
#    headers = [('Content-type', 'text/html')]
#    start_response(status, headers)
#    body=["Welcome to Baidu Cloud!\n"]
#    import flask
#    return "flask version: " + flask.__version__
#    return body

#from bae.core.wsgi import WSGIApplication
#application = WSGIApplication(app)
