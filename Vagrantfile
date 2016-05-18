Vagrant.configure(2) do |config|

  config.vm.box = "bento/centos-7.2"

  config.vm.provision "shell", inline: <<-SHELL
    sudo yum -y install epel-release
    sudo yum -y install rabbitmq-server php php-bcmath php-mbstring php-process
    sudo systemctl start rabbitmq-server
    curl -sS https://getcomposer.org/installer |
      sudo php -- --install-dir=/usr/local/bin --filename=composer
  SHELL

  config.vm.provider :virtualbox do |v|
    v.linked_clone = true
  end
end
